<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActiveUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(auth()->user()){
            $pass = User::where('email', auth()->user()->email)->first();
            if((boolean)$pass->verify_email === true){
                $pass = $pass->activate;
            }else{
                $pass = false;
            }
        }else{
            $pass1 = User::where('email', $request->email)->first();
            if(!$pass1){
                $pass = true;
            }else{
                $pass = $pass1->activate;
            }
        }
        if((boolean)$pass === false){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'User Deactivated or Email Not Verified'
            ], Response::HTTP_NOT_FOUND);
        }
        return $next($request);
    }
}
