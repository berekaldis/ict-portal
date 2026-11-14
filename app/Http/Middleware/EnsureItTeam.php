<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureItTeam
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // Must have case.view permission or be IT role / Super Admin
        if (
            $user->hasRole(['Super Admin', 'superadmin', 'IT Admin', 'IT Staff', 'IT']) ||
            $user->can('case.view')
        ) {
            return $next($request);
        }

        abort(403, 'Access restricted to Kaldis IT Team members only.');
    }
}
