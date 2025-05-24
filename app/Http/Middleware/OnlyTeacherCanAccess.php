<?php

namespace App\Http\Middleware;

use App\Constants\MessageConstants;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OnlyTeacherCanAccess
{
    use ApiResponseTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (!(auth('teacher')->user() instanceof \App\Models\Teacher)) {
            return $this->apiResponse('Unauthorized: Only teachers can access this resource.', MessageConstants::QUERY_NOT_EXECUTED, 403);
        }

        return $next($request);
    }
}
