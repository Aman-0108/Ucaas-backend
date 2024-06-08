<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminOrCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $userType = auth()->user()->usertype;

        if ($userType == 'SuperAdmin' || $userType == 'Company') {
            return $next($request);
        }

        $response = [
            'status' => false,
            'message' => 'You do not have privileges.'
        ];

        // Return a 403 Forbidden response
        return response()->json($response, Response::HTTP_FORBIDDEN);
    }
}
