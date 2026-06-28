<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

/**
 * Generates an OpenAPI 3.0 description of the versioned (/v1) API directly from
 * the registered routes — so the spec can never drift from what's actually wired.
 * Each endpoint's required Sanctum ability is read off its `ability:` middleware.
 */
class OpenApiController extends Controller
{
    public function spec(): JsonResponse
    {
        $paths = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/v1/') || str_contains($uri, 'openapi')) {
                continue;
            }

            $path = '/'.substr($uri, strlen('api/v1/')); // /invoices, /invoices/{invoice}
            $scope = $this->abilityOf($route->gatherMiddleware());

            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $verb = strtolower($method);
                $paths[$path][$verb] = [
                    'summary' => strtoupper($method).' '.$path,
                    'security' => $scope !== null ? [['sanctum' => [$scope]]] : [],
                    'responses' => ['200' => ['description' => 'OK']],
                ];
            }
        }

        ksort($paths);

        return response()->json([
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Liberu Accounting API',
                'version' => '1.0.0',
                'description' => 'Versioned REST API generated from the live routes. Authenticate with a Sanctum bearer token; each endpoint is scoped by token abilities (e.g. invoices:read, invoices:write).',
            ],
            'servers' => [['url' => '/api/v1']],
            'components' => [
                'securitySchemes' => [
                    'sanctum' => ['type' => 'http', 'scheme' => 'bearer'],
                ],
            ],
            'paths' => $paths,
        ]);
    }

    /**
     * Extract the `ability:<scope>` middleware's scope, if any.
     *
     * @param  list<string>  $middleware
     */
    private function abilityOf(array $middleware): ?string
    {
        foreach ($middleware as $m) {
            if (is_string($m) && str_starts_with($m, 'ability:')) {
                return substr($m, strlen('ability:'));
            }
        }

        return null;
    }
}
