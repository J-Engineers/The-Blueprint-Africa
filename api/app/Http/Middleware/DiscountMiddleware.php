<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Courses\Discounts;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DiscountMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)   $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $total_discounts = 0;
        $discount = Discounts::where(
            [
                ['discounts', '>', 0]
            ]
        )->get();
        foreach ($discount as $value) {
            $total_discounts += $value->discounts;
        }
        
        $is_discount = false;
        if($total_discounts > 0){
            $is_discount = true;
        }
        $request['is_discount'] = $is_discount;
        $request['total_discounts'] = $total_discounts;
        return $next($request);
    }
}
