<?php

namespace App\Http\Controllers\Auth;


use Carbon\Carbon;
use App\Models\User;
use App\Models\AccessToken;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PasswordResetToken;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Auth\Login;
use App\Http\Requests\Auth\Register;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Auth\VerifyEmail;
use App\Http\Requests\Auth\ResetPassword;
use App\Http\Requests\Auth\ForgotPassword;

class AuthController extends Controller
{
    /**
     * Handles user Login
     *
     * @param Request $request
     *
     * @return JsonResponse
    */

    public function login(Login $request){

        $request->validated();
        // Attempt to find the user by email
        $user = User::where('email', $request->email)->first();

        // Check if the user exists and the provided password matches the hashed password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'Authentication failed',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user_passward = PasswordResetToken::where('email', $request->email)->first();
        if($user_passward){

            $otp = $user_passward->token;

            $to_name = "Dear ...";
            $to_email = $request->email;
            $data = array(
            "name"=> $user->user_name,
            "body" => "Welcome to Blueprint Platform, 
            We are glad you are here. Visit the Link below to begin. 
            You requested to reset your password.",
            "link" => env('APP_URL').'/tutor/reset',
            'token' => $otp
            );
        
            if(!Mail::send("emails.registration", $data, function($message) use ($to_name, $to_email) {
            $message->to($to_email, $to_name)->subject("Forgot Password");
            $message->from(env("MAIL_USERNAME", "jeorgejustice@gmail.com"), "Welcome");
            })){

                return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Mail Not Sent'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'Passward Change has been requested',
            ], Response::HTTP_UNAUTHORIZED);
        }
        AccessToken::where('tokenable_id', $user->id)->delete();
        $token = $user->createToken($request->email)->plainTextToken; // Creating access_token
        $is_admin = $user->is_admin;

        return response()->json([
            "status_code" => Response::HTTP_OK, // 200
            "status" => "success",
            "message" => "User Authenticated Successfully",
            "data" => [
                "access_token" => $token,
                "email" => $user->email,
                "id" => $user->id,
                "is_admin" => $is_admin ? true : false,
                "verification" => $user->verify_email ? true : false,
                "user_type" => $user->user_type,
            ]
        ], Response::HTTP_OK); // returning response
    }


    /**
     * Handles user registration
     *
     * @param Request $request
     *
     * @return JsonResponse
    */

    public function register(Register $request)
    {

        $request->validated();


        if($request->org_id !== env('ORG_ID', "swatCat5MikrotikZssHr5Sha255")){
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'Invalid Request',
            ], Response::HTTP_UNAUTHORIZED);
        }


        // Attempt to find the user by email
        $user = User::where('email', $request->email)->first();

        // Check if the user exists
        if ($user) {
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'Email Taken',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $verify_token = $this->get_token();
        $to_name = "Dear ...";
        $to_email = $request->email;
        $data = array(
           "name"=> $request->user_name,
           "body" => "Welcome to Blueprint Platform, We are glad you are here. Type in this token in next page or click on the link below to open the page",
           "link" => env('APP_URL').'/admin/verification',
           'token' => $verify_token
        );
       
        if(!Mail::send("emails.registration", $data, function($message) use ($to_name, $to_email) {
           $message->to($to_email, $to_name)->subject("Blueprint Registration");
           $message->from(env("MAIL_USERNAME", "jeorgejustice@gmail.com"), "Welcome");
        })){

            return response()->json([
               'status_code' => Response::HTTP_NOT_FOUND,
               'status' => 'error',
               'message' => 'Mail Not Sent',
               'data' => $request
            ], Response::HTTP_NOT_FOUND);
        }

        $password= Hash::make($request->password);
        $user = User::create([
            'uuid' => (string)Str::uuid(),
            'user_name' => $request->user_name,
            'email' => $request->email,
            'is_admin' => false,
            'activate' => true,
            'password' => $password,
            'phone' => $request->phone,
            'verify_token' => $verify_token,
            'verify_email' => false,
            'user_type' => 'admin',
        ]);

        if(auth()->user()){
            auth()->user()->tokens()->delete();
        }

        return response()->json(
            [
                'status_code' => Response::HTTP_CREATED,
                'status' => 'success',
                'message' => 'User signed up successfully',
                'data'=> [
                    "email" => $user->email,
                    "user_name" => $user->user_name,
                    "verify_token" => $user->verify_token,
                    "id" => $user->id,
                    "is_admin" => $user->is_admin ? true : false,
                    "verification" => $user->verify_email ? true : false,
                    "user_type" => $user->user_type,
                ]
            ], Response::HTTP_CREATED
        );
    }

    /**
     * Handles token generation
     *
     * @param Request $request
     *
     * @return JsonResponse
    */

    public function get_token(){
        
        return  mt_rand(100000, 999999);
    }

    /**
     * Handles user registration email verification
     *
     * @param Request $request
     *
     * @return JsonResponse
    */

    public function send_registration_verification_email(VerifyEmail $request)
    {
        $request->validated();

        if(auth()->user()){
            auth()->user()->tokens()->delete();
        }

        // Attempt to find the user by email
        $user = User::where('email', $request->email)->first();

        // Check if the user exists
        if (!$user OR !($user->verify_token === $request->verify_token)) {
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'Invalid Token',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if the user has confrimed email
        if ($user->verify_email == 1) {
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'success',
                'message' => 'Verification Successful done already. Kindly login',
            ], Response::HTTP_UNAUTHORIZED);
        }
         
        $to_name = "Dear ...";
        $to_email = $request->email;
        $data = array(
           "name"=> $user->user_name,
           "body" => "Welcome to Blueprint Platform, We are glad you are here. Visit the Link below to begin",
           "link" => env('APP_URL').'/admin/verification'
        );
       
        if(!Mail::send("emails.registrationVerification", $data, function($message) use ($to_name, $to_email) {
           $message->to($to_email, $to_name)->subject("Registration Verification");
           $message->from(env("MAIL_USERNAME", "jeorgejustice@gmail.com"), "Welcome");
        })){

            return response()->json([
               'status_code' => Response::HTTP_NOT_FOUND,
               'status' => 'error',
               'message' => 'Mail Not Sent'
            ], Response::HTTP_NOT_FOUND);
        }

        $user->update(['verify_email' => true, 'verify_token' => 0, 'email_verified_at' => Carbon::now()]);

        AccessToken::where('tokenable_id', $user->id)->delete();
        $token = $user->createToken($request->email)->plainTextToken; // Creating access_token
         
        return response()->json([
           'status_code' => Response::HTTP_OK,
           'status' => 'success',
           'message' => 'Verification done, login.',
           'data' => [
               "access_token" => $token,
               "email" => $user->email,
               "is_admin" => $user->is_admin ? true : false,
               "verification" => $user->verify_email ? true : false,
            ]
        ], Response::HTTP_OK);
    }


    /**
     * Handles user logout
     *
     * @param Request $request
     *
     * @return JsonResponse
    */

    public function logout(Request $request){

        $fields = Validator::make($request->all(), [
            'api_key' => 'required|string'
        ]);
 
        if($fields->fails()){
            return response()->json([
                 'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY, // 422,
                 'status' => 'error',
                'message' => $fields->messages(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        auth()->user()->tokens()->delete();
        auth('sanctum')->user()->currentAccessToken()->delete();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'User logged out'
        ], Response::HTTP_OK);
    }


    /**
     * Handles user request passward
     *
     * @param Request $request
     *
     * @return JsonResponse
    */


    public function forgotPassword(ForgotPassword $request)
    {

        $request->validated();

        if(auth()->user()){
            auth()->user()->tokens()->delete();
        }


        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'User not found',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $otp = $this->get_token();

        $to_name = "Dear ...";
        $to_email = $request->email;
        $data = array(
           "name"=> $user->user_name,
           "body" => "Welcome to Blueprint Platform, 
           We are glad you are here. Visit the Link below to begin. 
           You requested to reset your password.",
           "link" => env('APP_URL').'/tutor/reset',
           'token' => $otp
        );
       
        if(!Mail::send("emails.registration", $data, function($message) use ($to_name, $to_email) {
           $message->to($to_email, $to_name)->subject("Forgot Password");
           $message->from(env("MAIL_USERNAME", "jeorgejustice@gmail.com"), "Welcome");
        })){

            return response()->json([
               'status_code' => Response::HTTP_NOT_FOUND,
               'status' => 'error',
               'message' => 'Mail Not Sent'
            ], Response::HTTP_NOT_FOUND);
        }

        $user_passward = PasswordResetToken::where('email', $request->email)->first();
        if($user_passward){
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        }


        PasswordResetToken::create([
            'email' => $request->email,
            'token' => $otp
        ]);


        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Token Sent to users Email',
            'data' => [
                'token' => $otp,
                'email' => $request->email,
            ]
        ], Response::HTTP_OK);
    }


    /**
     * Handles user password reset
     *
     * @param Request $request
     *
     * @return JsonResponse
    */

    public function resetPassword(ResetPassword $request)
    {

        $request->validated();

        if(auth()->user()){
            auth()->user()->tokens()->delete();
        }
    
        $user_password = PasswordResetToken::where('token', $request->otp_token)->first();
        if(!$user_password OR !User::where('email', $user_password->email)->first()){
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'Invalid Token',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = User::where('email', $user_password->email)->first();

        $to_name = "Dear ...";
        $to_email = $user->email;
        $data = array(
           "name"=> $user->user_name,
           "body" => "Welcome to Blueprint Platform, 
           We are glad you are here. Visit the Link below to begin. 
           You password  reset was successful.",
           "link" => env('APP_URL').'/admin/login'
        );
       
        if(!Mail::send("emails.registrationVerification", $data, function($message) use ($to_name, $to_email) {
           $message->to($to_email, $to_name)->subject("Password Reset");
           $message->from(env("MAIL_USERNAME", "jeorgejustice@gmail.com"), "Welcome");
        })){

            return response()->json([
               'status_code' => Response::HTTP_NOT_FOUND,
               'status' => 'error',
               'message' => 'Mail Not Sent'
            ], Response::HTTP_NOT_FOUND);
        }

        if($user_password){
            PasswordResetToken::where('email', $user_password->email)->delete();
        }

        $password = Hash::make($request->password);
        $user->update([
            'password' => $password
        ]);

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Password Reset Successful'
        ], Response::HTTP_OK);
    }
}
