<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\PaymentTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelSchemaConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_notes_column_persists(): void
    {
        $invoice = Invoice::factory()->create(['notes' => 'Handle with care']);

        $this->assertSame('Handle with care', $invoice->fresh()->notes);
    }

    public function test_category_parent_and_children_resolve(): void
    {
        $parent = Category::create(['name' => 'Assets']);
        $child = Category::create(['name' => 'Current Assets', 'parent_id' => $parent->id]);

        $this->assertTrue($parent->children->contains($child));
        $this->assertTrue($child->parent->is($parent));
    }

    public function test_payment_term_number_of_days_persists(): void
    {
        $term = PaymentTerm::create([
            'payment_term_name' => 'Net 30',
            'payment_term_description' => 'Due in 30 days',
            'payment_term_number_of_days' => 30,
        ]);

        $this->assertSame(30, (int) $term->fresh()->payment_term_number_of_days);
    }
}
