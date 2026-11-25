<?php

namespace App\Http\Middleware;

use App\Services\AdminActivity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Opening an admin section's list page clears its navigation badge for that staff member. */
class MarkAdminSectionSeen
{
    public function __construct(private AdminActivity $activity) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->user() && ($route = $request->route()?->getName())) {
            $this->activity->markSeen($request->user(), $route);
        }

        return $next($request);
    }
}
