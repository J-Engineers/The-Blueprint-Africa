<?php

namespace App\Http\Controllers\Courses;


use App\Http\Controllers\Controller;
use App\Http\Requests\Courses\CreateWalletRequest;
use Storage;
use App\Models\User;
use Illuminate\Support\Str;
use App\Http\Requests\Courses\StoreWalletRequest;
use App\Http\Requests\Courses\UpdateWalletRequest;
use App\Models\Courses\Wallet;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Helpers;

class WalletController extends Controller
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

        $query_response = Wallet::all();
        if(!$query_response && $query_response->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'payment not found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        $data = new Helpers();
        foreach($query_response as $query_response_data){
            $query_response_data->user = $data->user($query_response_data->user_id);
        
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Wallets',
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

        $query_response = Wallet::where([
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
        
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Wallets',
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

        $query_response = Wallet::where([
            ['id', '=', $request->wallet_id]
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
        
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Wallets',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CreateWalletRequest $request, Wallet $wallet)
    {
        // user

        $request->validated();

        $user = auth()->user();
       
        $ref_id = mt_rand(100000000, 999999999);

        $query_response = $wallet::create([
            'id' => (string)Str::uuid(),
            'user_id' => $user->id,
            'ref_id' => $ref_id,
            'bp_rate' => $request->bprate,
            'payment_amount' => $request->payment_amount
        ]);

        $url = env('PAYSTACK_PAYMENT_INITIAL_URL', "https://api.paystack.co/transaction/initialize");

        $fields = [

            'email' => $user->email,

            'amount' => (float)$request->payment_amount * 100,

            'callback_url' => env('APP_PAYMENT_WALLET_CALLBACK_URL', "http://localhost:8080/api/v1/wallet/payment/callback"),

            'metadata' => [
                "wallet_id" => $query_response->id
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
            'reference' => $reference,
            'adminConsnet' => null
        ]);

        $data = new Helpers();
        $query_response->user = $data->user($query_response->user_id);
        
        

        return response()->json(
            [
                'status_code' => Response::HTTP_CREATED,
                'status' => 'success',
                'message' => 'Wallet Transaction Initiated',
                'data'=> [$query_response, $result]
            ], Response::HTTP_CREATED
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWalletRequest $request, Wallet $wallet)
    {

        // user
        $request->validated();
        $user = auth()->user();


        // create curl resource

        $ch = curl_init();



        // set url

        curl_setopt($ch, CURLOPT_URL, env("PAYSTACK_PAYMENT_VERIFICATION_URL", "https://api.paystack.co/transaction/verify/").$request->trxref);



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

        $wallet_id = $result['data']['metadata']['wallet_id'];
        $payment_mode = $result['data']['channel'];
        $payment_status = $result['message'];

       

        $query_response = $wallet::where(
            [
                ['id', '=', $wallet_id],
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
    

        return response()->json(
            [
                'status_code' => Response::HTTP_CREATED,
                'status' => 'success',
                'message' => "Payment Completed",
                'data'=>  $query_response
            ], Response::HTTP_CREATED
        );
    }

     /**
     * Show the form for editing the specified resource.
     */
    public function prove_payment(Wallet $wallet, Request $request)
    {
        // user

        $payment = $wallet::where(
            [
                ['id', '=', $request->wallet_id]
            ]
        )->first();

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
        
        

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Prove uploaded',
            'data' => $payment
        ], Response::HTTP_ACCEPTED);
    }

    

    public function ushows(Wallet $wallet)
    {
        // user and admin
        $user = auth()->user();

        $query_response = $wallet::where(
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
        
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Wallet Transaction',
            'data' => $query_response
        ], Response::HTTP_ACCEPTED);
    }

    public function ushow(Wallet $wallet, Request $request)
    {
        // user and admin

        $user = auth()->user();

        $query_response = $wallet::where(
            [
                ['id', '=', $request->wallet_id],
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
        
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Wallet Transaction',
            'data' => $query_response
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Wallet $wallet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWalletRequest $request, Wallet $wallet)
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

        $query_response = $wallet::where(
            [
                ['id', '=', $request->wallet_id],
                ['adminConsent', '=', null]
            ]
        )
        ->select('user_id', 'adminConsent', 'payment_amount')
        ->first();
        if(!$query_response){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Transaction not found or already verified'
            ], Response::HTTP_NOT_FOUND);
        }

        $cal_bonus_bp = (float)$query_response->payment_amount / $request->bprate;

        $user_details = User::where(
            'id', $query_response->user_id
        )->select('wallet_bp_balance', 'verify_email', 'verify_token', 'email_verified_at')
        ->first();
        if(!$user_details){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'User Not Found'
            ], Response::HTTP_NOT_FOUND);
        }

        $new_wallet_bp_balance = (float)$user_details->wallet_bp_balance + $cal_bonus_bp;

        

        $user_details->update([
            'wallet_bp_balance' => $new_wallet_bp_balance,
            'verify_email' => true,
            'verify_token' => 0,
            'email_verified_at' => now()
        ]);

        $query_response->update(['adminConsent' => 1]);

        $data = new Helpers();
        $query_response->user = $data->user($query_response->user_id);
    

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Wallet Transaction Successful',
            'data' => [$query_response, $user_details]
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
    */
    public function destroy(Wallet $wallet, Request $request)
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

        $wallet::where('id', $request->subsciber_id)->delete();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'wallet Transaction removed',
            'data' => []
        ], Response::HTTP_ACCEPTED);
    }
}