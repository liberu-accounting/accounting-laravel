<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TeamsPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'You must be logged in to access this area.');
        }

        if (! $user->hasAnyRole(['admin', 'accountant', 'employee'])) {
            return redirect()->route('home')->with('error', 'You do not have permission to access this area.');
        }

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        if (! $user->currentTeam) {
            return redirect()->route('home')->with('error', 'You must be part of a team to access this area.');
        }

        $requestedTeamId = $request->route('tenant');
        if ($requestedTeamId && $requestedTeamId != $user->currentTeam->id) {
            return redirect()->route('staff.dashboard', ['tenant' => $user->currentTeam->id])
                ->with('error', 'You do not have permission to access this team.');
        }

        $routeName = $request->route()->getName();
        if (! $this->checkRoutePermissions($user, $routeName)) {
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        return $next($request);
    }

    private function checkRoutePermissions(mixed $user, ?string $routeName): bool
    {
        $permissionMap = [
            'users.*' => 'manage_users',
            'accounts.*' => 'manage_accounts',
            'transactions.*' => 'manage_transactions',
            'reports.*' => 'view_reports',
        ];

        return array_all($permissionMap, fn ($permission, $route): bool => ! (Str::is($route, $routeName) && ! $user->can($permission)));
    }
}
