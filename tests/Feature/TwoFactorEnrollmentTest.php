<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\App\Pages\EditProfile;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TwoFactorEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    public function test_admin_can_enrol_in_two_factor_end_to_end(): void
    {
        [$user] = $this->actingAsAdmin();

        $page = Livewire::test(EditProfile::class)->call('enableTwoFactor');

        // Enable generates the secret but does NOT enrol until confirmed (Fortify confirm => true).
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());

        // A valid TOTP from the authenticator app completes enrolment.
        $secret = decrypt($user->two_factor_secret);
        $code = (new Google2FA())->getCurrentOtp(is_string($secret) ? $secret : '');

        $page->set('twoFactorCode', $code)->call('confirmTwoFactor');

        $user->refresh();
        $this->assertTrue($user->hasEnabledTwoFactorAuthentication());
    }

    public function test_invalid_code_does_not_enrol(): void
    {
        [$user] = $this->actingAsAdmin();

        Livewire::test(EditProfile::class)
            ->call('enableTwoFactor')
            ->set('twoFactorCode', '000000')
            ->call('confirmTwoFactor');

        $this->assertFalse($user->refresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_enforced_unenrolled_admin_reaches_enrolment_ui(): void
    {
        // With enforcement on, the middleware sends un-enrolled admins to EditProfile;
        // that page must actually offer a way to enrol (no lockout).
        config(['accounting.enforce_2fa' => true]);
        $this->actingAsAdmin();

        Livewire::test(EditProfile::class)
            ->assertSee('Two-Factor Authentication')
            ->assertSee('Enable');
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function actingAsAdmin(): array
    {
        Role::findOrCreate('admin', 'web');

        $user = User::factory()->create();
        $team = Team::forceCreate([
            'user_id' => $user->id,
            'name' => 'Test Team',
            'personal_team' => true,
        ]);
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user->assignRole('admin');
        $user->refresh();

        $this->actingAs($user);
        Filament::setTenant($team);

        return [$user, $team];
    }
}
