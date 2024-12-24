

<?php

namespace Database\Seeders;

use App\Models\AccountTemplate;
use Illuminate\Database\Seeder;

class AccountTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Retail Business',
                'industry_type' => 'retail',
                'structure' => [
                    'assets' => [
                        'current_assets' => ['cash', 'inventory', 'accounts_receivable'],
                        'fixed_assets' => ['equipment', 'buildings']
                    ],
                    'liabilities' => [
                        'current_liabilities' => ['accounts_payable', 'short_term_loans'],
                        'long_term_liabilities' => ['long_term_loans', 'mortgages']
                    ],
                    'equity' => ['owner_equity', 'retained_earnings'],
                    'revenue' => ['sales', 'other_income'],
                    'expenses' => ['cost_of_goods_sold', 'operating_expenses', 'payroll']
                ]
            ],
            [
                'name' => 'Service Business',
                'industry_type' => 'service',
                'structure' => [
                    'assets' => [
                        'current_assets' => ['cash', 'accounts_receivable'],
                        'fixed_assets' => ['equipment', 'furniture']
                    ],
                    'liabilities' => [
                        'current_liabilities' => ['accounts_payable', 'taxes_payable'],
                        'long_term_liabilities' => ['loans']
                    ],
                    'equity' => ['owner_equity', 'retained_earnings'],
                    'revenue' => ['service_revenue', 'consulting_fees'],
                    'expenses' => ['salaries', 'rent', 'utilities', 'supplies']
                ]
            ],
            [
                'name' => 'Manufacturing',
                'industry_type' => 'manufacturing',
                'structure' => [
                    'assets' => [
                        'current_assets' => ['cash', 'raw_materials', 'work_in_progress', 'finished_goods'],
                        'fixed_assets' => ['machinery', 'factory_building']
                    ],
                    'liabilities' => [
                        'current_liabilities' => ['accounts_payable', 'wages_payable'],
                        'long_term_liabilities' => ['equipment_loans', 'mortgages']
                    ],
                    'equity' => ['owner_equity', 'retained_earnings'],
                    'revenue' => ['sales'],
                    'expenses' => ['raw_materials', 'direct_labor', 'manufacturing_overhead', 'administrative']
                ]
            ]
        ];

        foreach ($templates as $template) {
            AccountTemplate::create($template);
        }
    }
}