<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company(),
            'company_address' => $this->faker->address(),
            'company_email' => $this->faker->companyEmail(),
            'company_phone' => $this->faker->phoneNumber(),
            'company_city' => $this->faker->city(),
            'company_tin' => $this->faker->numerify('##########'),
            'hmrc_vat_number' => null,
            'hmrc_utr' => null,
            'hmrc_paye_reference' => null,
            'hmrc_accounts_office_reference' => null,
            'hmrc_corporation_tax_utr' => null,
            'vat_scheme' => null,
            'vat_period' => null,
        ];
    }
}
