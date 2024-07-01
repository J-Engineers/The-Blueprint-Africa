<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Courses\BP;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BPMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $total_discounts = 0;
        $discount = BP::where('rate', '>', 0)->get();
        foreach ($discount as $value) {
            $total_discounts += (float)$value->rate;
        }
        $request['bprate'] = $total_discounts;
        return $next($request);
    }
}
