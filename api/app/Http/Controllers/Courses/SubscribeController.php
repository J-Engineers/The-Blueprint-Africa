<?php

namespace App\Http\Controllers\Courses;

use Storage;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Courses\Referral;
use App\Http\Controllers\Helpers;
use App\Models\Courses\Subscribe;
use App\Http\Controllers\Controller;
use App\Models\Courses\AcademyCourses;
use App\Models\Courses\BlueprintCourses;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Courses\StoreSubscribeRequest;
use App\Http\Requests\Courses\CreateSubscribeRequest;
use App\Http\Requests\Courses\UpdateSubscribeRequest;
use App\Http\Requests\Courses\StoreWalletSubscribeRequest;

class SubscribeController extends Controller
{
    /**
     * Display a listing of the resource.
    */
    public function index()
    {
        // admin

        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $query_response = Subscribe::all();

        $data = new Helpers();
        foreach($query_response as $query_response_data){
            $query_response_data->user = $data->user($query_response_data->user_id);
            if($query_response_data->course_type== 'blueprint'){
                $query_response_data->course = $data->blueprint_course($query_response_data->course_id);
            }else{
                $query_response_data->course = $data->academy_course($query_response_data->course_id);
            }
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Courses Subscriptions',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    public function uindexs(Request $request)
    {
        // admin
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $query_response = Subscribe::where([
            ['user_id', '=', $request->user_id]
        ])->get();
        if($query_response->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'No Transactions Found'
            ], Response::HTTP_NOT_FOUND);
        }
        $data = new Helpers();
        foreach($query_response as $query_response_data){
            $query_response_data->user = $data->user($query_response_data->user_id);
            if($query_response_data->course_type== 'blueprint'){
                $query_response_data->course = $data->blueprint_course($query_response_data->course_id);
            }else{
                $query_response_data->course = $data->academy_course($query_response_data->course_id);
            }
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Courses',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    public function uindex(Request $request)
    {
        // admin
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $query_response = Subscribe::where([
            ['id', '=', $request->subscribe_id]
        ])->get();
        if($query_response->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'No Transaction Found'
            ], Response::HTTP_NOT_FOUND);
        }
        $data = new Helpers();
        foreach($query_response as $query_response_data){
            $query_response_data->user = $data->user($query_response_data->user_id);
            if($query_response_data->course_type== 'blueprint'){
                $query_response_data->course = $data->blueprint_course($query_response_data->course_id);
            }else{
                $query_response_data->course = $data->academy_course($query_response_data->course_id);
            }
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Courses',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CreateSubscribeRequest $request, Subscribe $subscribe)
    {
        // user

        $request->validated();

        $user = auth()->user();
       
        $ref_id = mt_rand(100000000, 999999999);

        if($request->course_type == 'blueprint'){
            $real_courses = BlueprintCourses::where('id', $request->course_id)->first();
        }else{
            $real_courses = AcademyCourses::where('id', $request->course_id)->first();

        }

        $payment_amount = $real_courses->price;
        if($request->is_discount == true){
            $payment_amount = ($real_courses->price * ($real_courses->discount_rate / 100));
        }

        $query_response = $subscribe::create([
            'id' => (string)Str::uuid(),
            'user_id' => $user->id,
            'course_id' => $request->course_id,
            'ref_id' => $ref_id,
            'scholarship' => $request->is_discount,
            'course_type' => $request->course_type,
            'bp_rate' => $request->bprate,
            'payment_amount' => $payment_amount
        ]);

        $url = env('PAYSTACK_PAYMENT_INITIAL_URL', "https://api.paystack.co/transaction/initialize");

        $fields = [

            'email' => $user->email,

            'amount' => (float)$payment_amount * 100,

            'callback_url' => env('APP_PAYMENT_SUBSCRIPTION_CALLBACK_URL', "http://localhost:8080/api/v1/subscription/payment/callback"),

            'metadata' => [
                "subscribe_id" => $query_response->id
            ]
        ];


        $fields_string = http_build_query($fields);


        //open connection

        $ch = curl_init();

        

        //set the url, number of POST vars, POST data

        curl_setopt($ch,CURLOPT_URL, $url);

        curl_setopt($ch,CURLOPT_POST, true);

        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(

            "Authorization: Bearer ".env('PAYSTACK_SECRET_KEY', 'sk_test_dea178488aed8c0dc7817e609d43a972818a8fdd'),

            "Cache-Control: no-cache",

        ));

        

        //So that curl_exec returns the contents of the cURL; rather than echoing it

        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 

        

        //execute post

        $result = curl_exec($ch);

        $result = json_decode($result, true);

        $access_code = $result['data']['access_code'];
        $reference = $result['data']['reference'];

        $query_response->update([
            'access_code' => $access_code,
            'reference' => $reference
        ]);

        $data = new Helpers();
        $query_response->user = $data->user($query_response->user_id);
        if($query_response->course_type== 'blueprint'){
            $query_response->course = $data->blueprint_course($query_response->course_id);
        }else{
            $query_response->course = $data->academy_course($query_response->course_id);
        }
        

        return response()->json(
            [
                'status_code' => Response::HTTP_CREATED,
                'status' => 'success',
                'message' => 'Course Transaction Initiated',
                'data'=> [$query_response, $result]
            ], Response::HTTP_CREATED
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubscribeRequest $request, Subscribe $subscribe)
    {
        // user
        $request->validated();
        $user = auth()->user();

        $query_response1 = $subscribe::where(
            [
                ['course_id', '=', $request->course_id],
                ['user_id', '=', $user->id]
            ]
        )->first();
        if($query_response1){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Baught course already'
            ], Response::HTTP_NOT_FOUND);
        }

        // create curl resource

        $ch = curl_init();



        // set url
        $url = env("PAYSTACK_PAYMENT_VERIFICATION_URL", "https://api.paystack.co/transaction/verify/").$request->trxref;

        curl_setopt($ch, CURLOPT_URL, $url);



        //return the transfer as a string

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(

            "Authorization: Bearer ".env('PAYSTACK_SECRET_KEY', 'sk_test_dea178488aed8c0dc7817e609d43a972818a8fdd'),

            "Cache-Control: no-cache",
        ));




        // $output contains the output string

        $result = curl_exec($ch);



        // close curl resource to free up system resources

        curl_close($ch); 

        $result = json_decode($result, true);
        if($result['status'] === false){
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => $result['message'],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $subscribe_id = $result['data']['metadata']['subscribe_id'];
        $payment_mode = $result['data']['channel'];
        $payment_status = $result['message'];

        $query_response = $subscribe::where(
            [
                ['id', '=', $subscribe_id],
                ['reference', '=', $request->trxref],
                ['user_id', '=', $user->id]
            ]
        )->first();
        if(!$query_response){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Invalid Payment'
            ], Response::HTTP_NOT_FOUND);
        }

        $query_response->update(
            [
                'payment_mode' => $payment_mode,
                'payment_status' => $payment_status
            ]
        );

        $data = new Helpers();
        $query_response->user = $data->user($query_response->user_id);
        if($query_response->course_type== 'blueprint'){
            $query_response->course = $data->blueprint_course($query_response->course_id);
        }else{
            $query_response->course = $data->academy_course($query_response->course_id);
        }

        return response()->json(
            [
                'status_code' => Response::HTTP_CREATED,
                'status' => 'success',
                'message' => 'Payment Completed',
                'data'=> $query_response
            ], Response::HTTP_CREATED
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store_wallet(StoreWalletSubscribeRequest $request, Subscribe $subscribe)
    {
        // user
        $request->validated();
        $user = auth()->user();

        $query_response1 = $subscribe::where(
            [
                ['course_id', '=', $request->course_id],
                ['user_id', '=', $user->id]
            ]
        )->first();
        if($query_response1){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Baught course already'
            ], Response::HTTP_NOT_FOUND);
        }
       
        $ref_id = mt_rand(100000000, 999999999);

        if($request->course_type == 'blueprint'){
            $real_courses = BlueprintCourses::where('id', $request->course_id)->first();
        }else{
            $real_courses = AcademyCourses::where('id', $request->course_id)->first();

        }

        $course_price = $real_courses->price;
        $payment_amount = $course_price;
        if($request->is_discount == true){
            $course_price =  ($real_courses->price * ($real_courses->discount_rate / 100));
            $payment_amount = $course_price;
        }

        $course_price_in_bp = $payment_amount/$request->bprate;

        $query_user = User::where([
            ['id', '=', $user->id]
        ])->first();

        if($query_user->wallet_bp_balance < $course_price_in_bp){

            $refferal_bp = Referral::where([
                ['user_id', '=', $query_user->id]
            ])->select('total_bp', 'used_bp', 'withdrawn_bp')
            ->first();

            $available = $query_user->referral_bp_balance;

            $new_balance = $available + $query_user->wallet_bp_balance;
            if($new_balance < $course_price_in_bp){
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'status' => 'error',
                    'message' => 'You do not have enough money in your main wallet and referral wallet to buy this course please fund your wallet and refer more people to the platform',
                    'data' => [
                        'course_price_bp' => $course_price_in_bp,
                        'wallet_balance_bp' => $query_user->wallet_bp_balance,
                        'referral_balance_bp' => $available,
                        'balance_bp' => $new_balance,
                    ]
                ], Response::HTTP_NOT_FOUND);
            }else{

                $used_referral_bp = $course_price_in_bp - $query_user->wallet_bp_balance;
                $remove_from_user = $query_user->referral_bp_balance - $used_referral_bp;
                $refferal_bp->update([
                    'used_bp' => ($refferal_bp->used_bp + $used_referral_bp)
                ]);

                $query_user->update([
                    'referral_bp_balance' => $remove_from_user,
                    'wallet_bp_balance' => 0
                ]);

                $query_response = $subscribe::create([
                    'id' => (string)Str::uuid(),
                    'user_id' => $user->id,
                    'course_id' => $request->course_id,
                    'ref_id' => $ref_id,
                    'scholarship' => $request->is_discount,
                    'course_type' => $request->course_type,
                    'bp_rate' => $request->bprate,
                    'payment_amount' => $payment_amount,
                    'payment_mode' => 'wallet',
                    'payment_status' => 'successful',
                    'access_code' => 'nil',
                    'reference' => 'nil'
                ]);
            }

        }else{
            $query_response = $subscribe::create([
                'id' => (string)Str::uuid(),
                'user_id' => $user->id,
                'course_id' => $request->course_id,
                'ref_id' => $ref_id,
                'scholarship' => $request->is_discount,
                'course_type' => $request->course_type,
                'bp_rate' => $request->bprate,
                'payment_amount' => $payment_amount,
                'payment_mode' => 'wallet',
                'payment_status' => 'successful',
                'access_code' => 'nil',
                'reference' => 'nil'
            ]);
            $remove_from_user = $query_user->wallet_bp_balance - $course_price_in_bp;
            $query_user->update([
                'wallet_bp_balance' => $remove_from_user
            ]);
        }

        $data = new Helpers();
        $query_response->user = $data->user($query_response->user_id);
        if($query_response->course_type== 'blueprint'){
            $query_response->course = $data->blueprint_course($query_response->course_id);
        }else{
            $query_response->course = $data->academy_course($query_response->course_id);
        }

        return response()->json(
            [
                'status_code' => Response::HTTP_CREATED,
                'status' => 'success',
                'message' => 'Payment Completed',
                'data'=> $query_response
            ], Response::HTTP_CREATED
        );
    }

    /**
     * Show the form for editing the specified resource.
    */
    public function prove_payment(Subscribe $subscribe, Request $request)
    {
        // user

        $payment = $subscribe::where('id', $request->subscribe_id)->first();
        if(!$payment){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'No Transaction found'
            ], Response::HTTP_NOT_FOUND);
        }

        if(!$request->hasfile('prove_of_payment'))
        {
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => 'Choose a prove of payment file and upload',
            ], Response::HTTP_UNAUTHORIZED);
        }

        

        $file = $request->file('prove_of_payment');
        $name=time().$file->getClientOriginalName();
        $filePath = 'images/' . $name;
        $disk = Storage::disk('s3');
        $disk->put($filePath, file_get_contents($file));
        $base_path = $disk->url($filePath);


        $payment->update([
            'prove_of_payment' => $base_path
        ]);

        $data = new Helpers();
        $payment->user = $data->user($payment->user_id);
        if($payment->course_type== 'blueprint'){
            $payment->course = $data->blueprint_course($payment->course_id);
        }else{
            $payment->course = $data->academy_course($payment->course_id);
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Prove uploaded',
            'data' => $payment
        ], Response::HTTP_ACCEPTED);
    }


    /**
     * Display the specified resource.
     */

    public function ushows(Subscribe $subscribe)
    {
        // user and admin
        $user = auth()->user();

        $query_response = $subscribe::where(
            [
                ['user_id', '=', $user->id]
            ]
        )->get();

        if($query_response->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'No Transactions found'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = new Helpers();
        foreach($query_response as $query_response_data){
            $query_response_data->user = $data->user($query_response_data->user_id);
            if($query_response_data->course_type== 'blueprint'){
                $query_response_data->course = $data->blueprint_course($query_response_data->course_id);
            }else{
                $query_response_data->course = $data->academy_course($query_response_data->course_id);
            }
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Courses Transaction',
            'data' => $query_response
        ], Response::HTTP_ACCEPTED);
    }

    public function ushow(Subscribe $subscribe, Request $request)
    {
        // user and admin

        $user = auth()->user();

        $query_response = $subscribe::where(
            [
                ['id', '=', $request->subscribe_id],
                ['user_id', '=', $user->id]
            ]
        )->get();
        if($query_response->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'No Transactions found'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = new Helpers();
        foreach($query_response as $query_response_data){
            $query_response_data->user = $data->user($query_response_data->user_id);
            if($query_response_data->course_type== 'blueprint'){
                $query_response_data->course = $data->blueprint_course($query_response_data->course_id);
            }else{
                $query_response_data->course = $data->academy_course($query_response_data->course_id);
            }
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Wallet Transaction',
            'data' => $query_response
        ], Response::HTTP_ACCEPTED);
    }
   

   
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubscribeRequest $request, Subscribe $subscribe)
    {
        // admin

        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $query_response = $subscribe::where(
            [
                ['id', '=', $request->subscribe_id],
                ['adminConsent', '=', null]
            ]
        )
        ->first();
        if(!$query_response){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Transaction not found or already verified'
            ], Response::HTTP_NOT_FOUND);
        }
        if($query_response->course_type == 'blueprint'){
            $query_course = BlueprintCourses::where(
                [
                    ['id', '=', $query_response->course_id]
                ]
            )
            ->first();
        }else{
            $query_course = AcademyCourses::where(
                [
                    ['id', '=', $query_response->course_id]
                ]
            )
            ->first();
        }

        
        if(!$query_course){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Course not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $user_details = User::where(
            [
                ['id', '=', $query_response->user_id]
            ]
        )
        ->first();
        if(!$user_details){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'User not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $referral = User::where(
            [
                ['id', '=', $user_details->referredby_user_id]
            ]
        )
        ->first();
        if($referral){
            $cal_bonus = $query_response->payment_amount * ($query_course->referral_bonus_rate / 100);
            $cal_bonus_bp = $cal_bonus/ $request->bprate;
            $new_referral_bp_balance = $referral->referral_bp_balance + $cal_bonus_bp;
            $referral->update(['referral_bp_balance' => $new_referral_bp_balance]);

            $withdraw_user_ref = Referral::where(
                [
                    ['user_id', '=', $user_details->referredby_user_id]
                ]
            )->first();
            if($withdraw_user_ref){
                $total_bp = $withdraw_user_ref->total_bp + $cal_bonus_bp;
                $withdraw_user_ref->update(['total_bp' => $total_bp]);
            }
        }

        $referral_link = mt_rand(10000000, 99999999);
        $query_response->update(['adminConsent' => 1]);
        if(\is_null($user_details->referral_link)){
            $user_details->update(['referral_link' => $referral_link]);
        }
        $user_details->update(['verify_email' => true, 'verify_token' => 0, 'email_verified_at' => now()]);

        $data = new Helpers();
        $query_response->user = $data->user($query_response->user_id);
        if($query_response->course_type== 'blueprint'){
            $query_response->course = $data->blueprint_course($query_response->course_id);
        }else{
            $query_response->course = $data->academy_course($query_response->course_id);
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Course Subscription Successful',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subscribe $subscribe, Request $request)
    {
        // admin

        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $subscribe::where('id', $request->subscibe_id)->delete();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'subcription removed',
            'data' => []
        ], Response::HTTP_ACCEPTED);
    }
}
