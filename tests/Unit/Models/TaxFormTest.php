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
}
