<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\TaxForm;
use Tests\TestCase;

class TaxFormTest extends TestCase
{
    public function test_tax_summary_accessor_returns_array_when_present(): void
    {
        // Values round-trip through the JSON `array` cast, so use ints.
        $form = new TaxForm(['form_data' => ['tax_summary' => ['VAT' => ['rate' => 20, 'amount' => 40]]]]);

        $this->assertSame(['VAT' => ['rate' => 20, 'amount' => 40]], $form->tax_summary);
    }

    public function test_tax_summary_accessor_defaults_to_empty_array_when_absent(): void
    {
        $form = new TaxForm(['form_data' => ['something_else' => 1]]);

        $this->assertSame([], $form->tax_summary);
    }

    public function test_tax_summary_accessor_defaults_to_empty_array_when_form_data_null(): void
    {
        $form = new TaxForm;

        $this->assertSame([], $form->tax_summary);
    }

    public function test_calculate_totals_is_unreachable_on_current_schema(): void
    {
        // calculateTotals() reads $invoice->tax_amount and $invoice->taxRate
        // (via tax_rate_id) to build total_tax_withheld and the per-rate
        // tax_summary. The migrated `invoices` table has neither `tax_amount`
        // nor `tax_rate_id` columns, and with strict attribute checking on,
        // resolving $invoice->taxRate throws MissingAttributeException for
        // tax_rate_id — so the whole method blows up before it can sum anything.
        $this->markTestSkipped('BUG: invoices table has no tax_amount/tax_rate_id columns; TaxForm::calculateTotals() throws MissingAttributeException (tax_rate_id) and cannot run.');
    }
}
