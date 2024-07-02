<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Courses\StorePaymentDetailsRequest;
use App\Http\Requests\Courses\UpdatePaymentDetailsRequest;
use App\Models\Courses\PaymentDetails;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Http\Controllers\Helpers;

class PaymentDetailsController extends Controller
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

        $query_response = PaymentDetails::where(
            [
                ['account_number', '!=', '0']
            ]
        )->get();
        if(!$query_response && $query_response->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Payment Details Not found'
            ], Response::HTTP_NOT_FOUND);
        }

        foreach($query_response as $query_response_data){
            $data = new Helpers();
            $query_response_data->user = $data->user($query_response_data->user_id);
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Payment Details',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $user = auth()->user();
        PaymentDetails::where(
            [
                ['user_id', '=', $user->id],
                ['account_number', '=', '0']
            ]
        )->delete();

        $otp = $this->get_token();

        $to_name = "Dear ...";
        $to_email = $user->email;
        $data = array(
           "name"=> $user->user_name,
           "body" => "Welcome to Blueprint Platform, We are glad you are here. Type in this token in next page.",
           "link" => env('APP_URL').'/user/payment/details',
           'token' => $otp
        );
       
        if(!Mail::send("emails.registration", $data, function($message) use ($to_name, $to_email) {
           $message->to($to_email, $to_name)->subject("Blueprint Payment Details");
           $message->from(env("MAIL_USERNAME", "jeorgejustice@gmail.com"), "Welcome");
        })){

            return response()->json([
               'status_code' => Response::HTTP_NOT_FOUND,
               'status' => 'error',
               'message' => 'Mail Not Sent',
               'data' => []
            ], Response::HTTP_NOT_FOUND);
        }

        $query_response = PaymentDetails::create([
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
                'message' => 'Payment Details Request Initiated',
                'data'=> $query_response
            ], Response::HTTP_CREATED
        );
    }

    public function get_token(){
        
        return  mt_rand(100000, 999999);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentDetailsRequest $request)
    {
        //
        $request->validated();
        $user = auth()->user();

        $query_response = PaymentDetails::where(
            [
                ['user_id', '=', $user->id],
                ['otp', '=', $request->otp]
            ]
        )->first();
        if(!$query_response){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Payment not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $otp = $this->get_token();

        $query_response->update(
            ['account_name' => $request->account_name, 'bank_name' => $request->bank_name, 'account_number' => $request->account_number, 'otp' => $otp ]
        );

        $data = new Helpers();
        $query_response->user = $data->user($query_response->user_id);
        
        return response()->json(
            [
                'status_code' => Response::HTTP_CREATED,
                'status' => 'success',
                'message' => 'Payment Details Request Completed',
                'data'=> $query_response
            ], Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentDetails $paymentDetails, Request $request)
    {
        $request->validated();
        $query_response = PaymentDetails::where(
            [
                ['account_number', '!=', '0'],
                ['user_id', '=', $request->user_id]
            ]
        )->get();
         if(!$query_response && $query_response->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'Payment Details Not found'
            ], Response::HTTP_NOT_FOUND);
        }
        foreach($query_response as $query_response_data){
            $data = new Helpers();
            $query_response_data->user = $data->user($query_response_data->user_id);
        }
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Payment Details',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentDetails $paymentDetails)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentDetailsRequest $request, PaymentDetails $paymentDetails)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentDetails $paymentDetails, Request $request)
    {

        $paymentDetails::where('id', $request->payment_details_id)->delete();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Payment Details removed',
            'data' => []
        ], Response::HTTP_ACCEPTED);
    }
}