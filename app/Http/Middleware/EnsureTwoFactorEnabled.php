<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Filament\App\Pages\EditProfile;
use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces users who are subject to the 2FA policy to enrol before they can use a
 * panel. Fortify ships the enrolment mechanics but no "required" switch — this
 * app-level middleware is the standard way to make it mandatory.
 */
class EnsureTwoFactorEnabled
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user instanceof User
            || ! $this->shouldEnforce($user)
            || $user->hasEnabledTwoFactorAuthentication()   // Fortify trait: secret + confirmed_at
            || $this->onAllowedRoute($request)) {
            return $next($request);
        }

        // ponytail: a tenant panel needs a tenant to build the profile URL. A
        // team-less user can't reach tenant data anyway, so let them through
        // (they land on tenant registration) rather than 500 on URL generation.
        if (Filament::getCurrentPanel()?->hasTenancy() && $user->currentTeam === null) {
            return $next($request);
        }

        return redirect(EditProfile::getUrl(tenant: $user->currentTeam))->with(
            'error',
            'Two-factor authentication is required for your account. Please enable it before continuing.',
        );
    }

    /**
     * Whether 2FA is mandatory for this user.
     *
     * ponytail: privileged roles only — the lightest gate that already exists
     * here (Spatie roles: `super_admin` guards Horizon, `admin` guards
     * TeamsPermission). Widen by adding roles, or swap the body for
     * Gate::allows('require-2fa', $user) / a teams.enforce_2fa flag to gate
     * per-team instead.
     */
    protected function shouldEnforce(User $user): bool
    {
        // ponytail: OFF by default — the EditProfile page has no 2FA enrolment form
        // yet, so enforcing without it would redirect-loop admins into a lockout.
        // Ships dormant; flip config('accounting.enforce_2fa') once enrolment UI lands.
        if (! config('accounting.enforce_2fa', false)) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Routes that must stay reachable so an enforced user can enrol or escape —
     * including the redirect target itself, which is the loop guard.
     */
    private function onAllowedRoute(Request $request): bool
    {
        $name = $request->route()?->getName();

        if ($name === null) {
            return false;
        }

        $panel = Filament::getCurrentPanel();
        $panelId = $panel?->getId() ?? 'app';

        return in_array($name, [
            EditProfile::getRouteName($panel),   // redirect target — prevents a loop
            "filament.{$panelId}.auth.logout",   // escape hatch
            // Fortify 2FA enrolment endpoints — present once its routes are enabled:
            'two-factor.enable',
            'two-factor.confirm',
            'two-factor.disable',
            'two-factor.qr-code',
            'two-factor.recovery-codes',
            'two-factor.secret-key',
            'profile.show',
        ], true);
    }
}
