<?php

namespace App\Http\Middleware;

use App\Constants\MessageConstants;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OnlyUserCanAccess
{
    use ApiResponseTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    { 

        // dd(auth()->user());
        if (!(auth('user')->user() instanceof \App\Models\User)) {

            return $this->apiResponse('Unauthorized: Only users can access this resource.', MessageConstants::QUERY_NOT_EXECUTED, 403);
        }

        return $next($request);
    }
}
