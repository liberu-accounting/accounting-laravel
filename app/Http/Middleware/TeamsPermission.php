<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamsPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'You must be logged in to access this area.');
        }

        // Check if user has any of the required roles
        if (!$user->hasAnyRole(['admin', 'accountant', 'employee'])) {
            return redirect()->route('home')->with('error', 'You do not have permission to access this area.');
        }

        // Allow admin users to access without team restrictions
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        if (!$user->currentTeam) {
            return redirect()->route('home')->with('error', 'You must be part of a team to access this area.');
        }

        // Check if the requested team matches the user's current team
        $requestedTeamId = $request->route('tenant');
        if ($requestedTeamId && $requestedTeamId != $user->currentTeam->id) {
            return redirect()->route('staff.dashboard', ['tenant' => $user->currentTeam->id])
                ->with('error', 'You do not have permission to access this team.');
        }

        // Check specific permissions based on the route
        $routeName = $request->route()->getName();
        if (!$this->checkRoutePermissions($user, $routeName)) {
            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
        }

        return $next($request);
    }

    private function checkRoutePermissions($user, $routeName)
    {
        $permissionMap = [
            'users.*' => 'manage_users',
            'accounts.*' => 'manage_accounts',
            'transactions.*' => 'manage_transactions',
            'reports.*' => 'view_reports',
        ];

        foreach ($permissionMap as $route => $permission) {
            if (str_is($route, $routeName) && !$user->can($permission)) {
                return false;
            }
        }

        return true;
    }
}
