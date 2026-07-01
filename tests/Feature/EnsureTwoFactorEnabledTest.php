<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\App\Pages\EditProfile;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class EnsureTwoFactorEnabledTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
        Role::findOrCreate('admin', 'web');
        config(['accounting.enforce_2fa' => true]); // enforcement is dormant by default
    }

    public function test_dormant_by_default_lets_enforced_user_through(): void
    {
        config(['accounting.enforce_2fa' => false]);
        $user = $this->enforcedUser(); // privileged admin, no 2FA

        // Safety: with the flag off (default), no lockout — passes through.
        $this->assertSame('ok', $this->runMiddleware($user, 'filament.app.pages.dashboard')->getContent());
    }

    public function test_enforced_user_without_2fa_is_redirected_to_profile(): void
    {
        $user = $this->enforcedUser();

        $response = $this->runMiddleware($user, 'filament.app.pages.dashboard');

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertSame(
            EditProfile::getUrl(tenant: $this->team),
            $response->headers->get('Location'),
        );
    }

    public function test_setup_page_and_logout_stay_reachable(): void
    {
        $user = $this->enforcedUser();

        // The redirect target (loop guard) and the escape hatch must pass through.
        foreach (['filament.app.pages.edit-profile', 'filament.app.auth.logout'] as $allowed) {
            $this->assertSame('ok', $this->runMiddleware($user, $allowed)->getContent(), $allowed);
        }
    }

    public function test_user_with_2fa_enabled_passes_through(): void
    {
        $user = $this->enforcedUser();
        $user->forceFill([
            'two_factor_secret' => 'stub-secret',
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->assertSame('ok', $this->runMiddleware($user, 'filament.app.pages.dashboard')->getContent());
    }

    public function test_user_not_subject_to_enforcement_passes_through(): void
    {
        $user = User::factory()->create(); // no privileged role, no 2FA

        $this->assertSame('ok', $this->runMiddleware($user, 'filament.app.pages.dashboard')->getContent());
    }

    private function enforcedUser(): User
    {
        $user = User::factory()->create();
        $this->team = Team::forceCreate([
            'user_id' => $user->id,
            'name' => 'Test Team',
            'personal_team' => true,
        ]);
        $user->forceFill(['current_team_id' => $this->team->id])->save();
        $user->assignRole('admin');

        return $user->fresh();
    }

    private function runMiddleware(User $user, string $routeName): Response
    {
        $this->actingAs($user);

        $request = Request::create('/app', 'GET');
        $request->setRouteResolver(fn (): Route => (new Route('GET', '/app', []))->name($routeName));

        return (new EnsureTwoFactorEnabled())->handle(
            $request,
            fn (): Response => new Response('ok'),
        );
    }
}
