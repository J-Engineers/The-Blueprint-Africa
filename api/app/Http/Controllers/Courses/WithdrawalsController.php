<?php

namespace App\Http\Controllers\Courses;


use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Courses\Withdrawals;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\Courses\StoreWithdrawalsRequest;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Courses\Referral;
use App\Http\Controllers\Helpers;

class WithdrawalsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $query_response = Withdrawals::where(
            [
                ['bp', '!=', '0']
            ]
        )->get();
        if(!$query_response && $query_response->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'withdrawal not found'
            ], Response::HTTP_NOT_FOUND);
        }

        foreach($query_response as $query_response_data){
            $data = new Helpers();
            $query_response_data->user = $data->user($query_response_data->user_id);
            $query_response_data->payment_details = $data->payment_details($query_response_data->payment_details_id);
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Withdrawal Request',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    public function get_token(){
        
        return  mt_rand(100000, 999999);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $user = auth()->user();
        Withdrawals::where(
            [
                ['user_id', '=', $user->id],
                ['bp', '=', '0']
            ]
        )->delete();

        $otp = $this->get_token();

        $to_name = "Dear ...";
        $to_email = $user->email;
        $data = array(
           "name"=> $user->user_name,
           "body" => "Welcome to Blueprint Platform, We are glad you are here. Type in this token in next page.",
           "link" => env('APP_URL').'/user/withdrawal',
           'token' => $otp
        );
       
        if(!Mail::send("emails.registration", $data, function($message) use ($to_name, $to_email) {
           $message->to($to_email, $to_name)->subject("Blueprint Withdrawal Request");
           $message->from(env("MAIL_USERNAME", "jeorgejustice@gmail.com"), "Welcome");
        })){

            return response()->json([
               'status_code' => Response::HTTP_NOT_FOUND,
               'status' => 'error',
               'message' => 'Mail Not Sent',
               'data' => []
            ], Response::HTTP_NOT_FOUND);
        }
        
        $query_response = Withdrawals::create([
            'id' => (string)Str::uuid(),
            'user_id' => $user->id,
            'otp' => $otp
        ]);
        

        $data = new Helpers();
        $query_response->user = $data->user($query_response->user_id);
        

        return response()->json(
            [
                'status_code' => Response::HTTP_CREATED,
                'status' => 'success',
                'message' => 'Withdrawal Request Initiated',
                'data'=> $query_response
            ], Response::HTTP_CREATED
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWithdrawalsRequest $request)
    {
        $request->validated();
        $user = auth()->user();

        $query_response = Withdrawals::where(
            [
                ['user_id', '=', $user->id],
                ['otp', '=', $request->otp]
            ]
        )->first();

        if(!$query_response ){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Withdrawal not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $otp = $this->get_token();

        $total_unpaid = 0;
        $query_unpaid = Withdrawals::where(
            [
                ['user_id', '=', $user->id],
                ['payment_details_id', '!=', null],
                ['adminConsent', '=', null]

            ]
        )->select("bp")->get();
        foreach ($query_unpaid as $value) {
            $total_unpaid += (float)$value->bp;
        }

        // Be sure the user has upto that amount in his wallet balance
        if((float)$user->referral_bp_balance < ((float)$request->bp + $total_unpaid)){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Not Enough Balance to withdraw',
                'data' => []
            ], Response::HTTP_NOT_FOUND);
        }

        $query_response->update(
            [
                'payment_details_id' => $request->payment_details_id,
                'otp' => $otp,
                'bp' => $request->bp,
                'rate' => $request->bprate,
                'description' => $request->description
            ]
        );

        $data = new Helpers();
        $query_response->user = $data->user($query_response->user_id);
        $query_response->payment_details = $data->payment_details($query_response->payment_details_id);
        return response()->json(
            [
                'status_code' => Response::HTTP_CREATED,
                'status' => 'success',
                'message' => 'Withdrawal Request Completed',
                'data'=> $query_response
            ], Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Withdrawals $withdrawals, Request $request)
    {

        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $query_response = Withdrawals::where(
            [
                ['rate', '!=', '0'],
                ['user_id', '=', $request->user_id]
            ]
        )->get();
        if(!$query_response && $query_response->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Withdrawal not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = new Helpers();
        foreach($query_response as $qr){
            $qr->user = $data->user($qr->user_id);
            $qr->payment_details = $data->payment_details($qr->payment_details_id);
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Withdrawal request',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    public function show_admin(Withdrawals $withdrawals, Request $request)
    {
        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }
        $query_response = Withdrawals::where(
            [
                ['rate', '!=', '0'],
                ['user_id', '=', $request->user_id],
                ['id', '=', $request->withdrawal_id]

            ]
        )->get();
        if(!$query_response && $query_response->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Withdrawal not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = new Helpers();
        foreach($query_response as $qr){
            $qr->user = $data->user($qr->user_id);
            $qr->payment_details = $data->payment_details($qr->payment_details_id);
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'User Withdrawal',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    

    public function show_all_user(Withdrawals $withdrawals, Request $request)
    {
        $user = auth()->user();
        $query_response = Withdrawals::where(
            [
                ['rate', '!=', '0'],
                ['user_id', '=', $user->id]
            ]
        )->get();
        if($query_response && $query_response->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Withdrawal not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = new Helpers();
        foreach($query_response as $qr){
            $qr->user = $data->user($qr->user_id);
            $qr->payment_details = $data->payment_details($qr->payment_details_id);
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'User Withdrawals',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    public function show_user(Withdrawals $withdrawals, Request $request)
    {
        $user = auth()->user();
        $query_response = Withdrawals::where(
            [
                ['rate', '!=', '0'],
                ['user_id', '=', $user->id],
                ['id', '=', $request->withdrawal_id]

            ]
        )->get();
        if($query_response && $query_response->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Withdrawal not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = new Helpers();
        foreach($query_response as $qr){
            $qr->user = $data->user($qr->user_id);
            $qr->payment_details = $data->payment_details($qr->payment_details_id);
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'User Withdrawal',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Withdrawals $withdrawals)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Withdrawals $withdrawals)
    {

        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $query_response = Withdrawals::where(
            [
                ['id', '=', $request->withdrawal_id]
            ]
        )
        ->select('user_id', 'adminConsent', 'bp')
        ->first();

        if($user->id == $query_response->user_id){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Cannot approve a withdrawal for yourself'
            ], Response::HTTP_NOT_FOUND);
        }

        $withdraw_user = User::where(
            [
                ['id', '=', $query_response->user_id]
            ]
        )->first();
        if(!$withdraw_user){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Cannot find user'
            ], Response::HTTP_NOT_FOUND);
        }
        $new_referral_bp_balance = (float)$withdraw_user->referral_bp_balance - (float)$query_response->bp;


        $withdraw_user_ref = Referral::where(
            [
                ['user_id', '=', $query_response->user_id]
            ]
        )->first();
        
        $withdrawn_bp = (float)$withdraw_user_ref->withdrawn_bp + (float)$query_response->bp;
        

        $query_response->update(['adminConsent' => 1]);
        $withdraw_user->update(['referral_bp_balance' => $new_referral_bp_balance]);
        $withdraw_user_ref->update(['withdrawn_bp' => $withdrawn_bp]);

        $data = new Helpers();
        $query_response->user = $data->user($query_response->user_id);
        $query_response->payment_details = $data->payment_details($query_response->payment_details_id);
        

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Withdrawal request',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Withdrawals $withdrawals, Request $request)
    {

        $user = auth()->user();
        if(!$user->is_admin === true){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Unauthorized'
            ], Response::HTTP_NOT_FOUND);
        }

        $withdrawals::where('id', $request->withdrawal_id)->delete();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Withdrawal removed',
            'data' => []
        ], Response::HTTP_ACCEPTED);
    }
}