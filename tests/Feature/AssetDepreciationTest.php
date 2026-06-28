<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\DepreciationCalculation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetDepreciationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAsset(string $method, float $cost, float $salvage, int $life): Asset
    {
        return Asset::create([
            'asset_name' => 'Test Asset',
            'asset_cost' => $cost,
            'useful_life_years' => $life,
            'depreciation_method' => $method,
            'salvage_value' => $salvage,
            'acquisition_date' => '2024-01-01',
            'is_active' => true,
        ]);
    }

    /** Straight line: (cost - salvage) / life. 10000, salvage 1000, 5yr => 1800/yr. */
    public function test_straight_line_annual_depreciation(): void
    {
        $asset = $this->makeAsset('straight_line', 10000, 1000, 5);
        $asset->calculateDepreciation();

        $rows = DepreciationCalculation::where('asset_id', $asset->asset_id)
            ->orderBy('year')->get();

        $this->assertCount(5, $rows);
        $this->assertEqualsWithDelta(1800.0, (float) $rows[0]->depreciation_amount, 0.001);
        $this->assertEqualsWithDelta(1800.0, (float) $rows[0]->accumulated_depreciation, 0.001);
        $this->assertEqualsWithDelta(8200.0, (float) $rows[0]->book_value, 0.001);
    }

    /** Multi-period straight line: book value never drops below salvage; final accumulated = depreciable amount. */
    public function test_straight_line_schedule_floors_at_salvage(): void
    {
        $asset = $this->makeAsset('straight_line', 10000, 1000, 5);
        $asset->calculateDepreciation();

        $rows = DepreciationCalculation::where('asset_id', $asset->asset_id)
            ->orderBy('year')->get();

        // After 3 of 5 years: accumulated 5400, book value 4600.
        $this->assertEqualsWithDelta(5400.0, (float) $rows[2]->accumulated_depreciation, 0.001);
        $this->assertEqualsWithDelta(4600.0, (float) $rows[2]->book_value, 0.001);

        // Final year: fully depreciated to salvage.
        $this->assertEqualsWithDelta(9000.0, (float) $rows[4]->accumulated_depreciation, 0.001);
        $this->assertEqualsWithDelta(1000.0, (float) $rows[4]->book_value, 0.001);
    }

    /** Reducing balance (double declining): rate = 2/life. 10000, 5yr => 40% => first year 4000. */
    public function test_reducing_balance_first_period(): void
    {
        $asset = $this->makeAsset('reducing_balance', 10000, 1000, 5);
        $asset->calculateDepreciation();

        $rows = DepreciationCalculation::where('asset_id', $asset->asset_id)
            ->orderBy('year')->get();

        // Year 1: 10000 * 0.4 = 4000, book value 6000.
        $this->assertEqualsWithDelta(4000.0, (float) $rows[0]->depreciation_amount, 0.001);
        $this->assertEqualsWithDelta(6000.0, (float) $rows[0]->book_value, 0.001);

        // Year 2: 6000 * 0.4 = 2400, book value 3600, accumulated 6400.
        $this->assertEqualsWithDelta(2400.0, (float) $rows[1]->depreciation_amount, 0.001);
        $this->assertEqualsWithDelta(3600.0, (float) $rows[1]->book_value, 0.001);
        $this->assertEqualsWithDelta(6400.0, (float) $rows[1]->accumulated_depreciation, 0.001);
    }
}
