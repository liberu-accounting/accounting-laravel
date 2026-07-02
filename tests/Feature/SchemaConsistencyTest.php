<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

/**
 * Permanent guard against model↔schema drift — the class of bug SQLite hides but
 * MySQL/prod hard-errors on: a `$fillable` entry or a `belongsTo` foreign key that
 * has no backing column (mass-assign error / always-null relation). Runs on the
 * migrated test schema, so it guards on any driver, including CI's SQLite.
 */
class SchemaConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_model_fillable_and_belongsto_fk_map_to_real_columns(): void
    {
        $problems = [];

        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');
            if (! class_exists($class)) {
                continue;
            }

            $ref = new ReflectionClass($class);
            if ($ref->isAbstract() || ! $ref->isSubclassOf(Model::class)) {
                continue;
            }

            try {
                $model = new $class;
                $table = $model->getTable();

                if (! Schema::hasTable($table)) {
                    $problems[] = "{$class}: table '{$table}' does not exist";

                    continue;
                }

                $columns = Schema::getColumnListing($table);

                foreach (array_diff($model->getFillable(), $columns) as $missing) {
                    $problems[] = "{$class}: \$fillable '{$missing}' is not a column of '{$table}'";
                }

                foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->getDeclaringClass()->getName() !== $class
                        || $method->isStatic()
                        || $method->getNumberOfRequiredParameters() > 0) {
                        continue;
                    }

                    $returnType = $method->getReturnType();
                    if (! $returnType instanceof ReflectionNamedType
                        || ! str_contains($returnType->getName(), 'BelongsTo')) {
                        continue;
                    }

                    try {
                        $relation = $model->{$method->getName()}();
                        if ($relation instanceof BelongsTo
                            && ! in_array($relation->getForeignKeyName(), $columns, true)) {
                            $problems[] = "{$class}::{$method->getName()}() foreign key '"
                                .$relation->getForeignKeyName()."' is not a column of '{$table}'";
                        }
                    } catch (\Throwable) {
                        // Relation that needs state to resolve — out of scope for a static column check.
                    }
                }
            } catch (\Throwable $e) {
                $problems[] = "{$class}: ".$e->getMessage();
            }
        }

        $this->assertSame([], $problems, "Model/schema drift detected:\n".implode("\n", $problems));
    }
}
