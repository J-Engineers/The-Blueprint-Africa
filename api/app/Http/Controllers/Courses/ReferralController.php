<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Courses\CreateReferralRequest;
use App\Http\Requests\Courses\StoreReferralRequest;
use App\Http\Requests\Courses\UpdateReferralRequest;
use App\Models\Courses\Referral;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Storage;
use App\Http\Controllers\Helpers;

class ReferralController extends Controller
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

        $referral = DB::table('referrals As r')
            ->leftJoin('users As u', function($join){
                $join->on('r.user_id', '=', 'u.id');
            })
            ->where(
            [
                    // ['u.referral_link', '!=', null]
                ]
            )
            ->select(
                'r.total_bp as total_bp', 'r.used_bp as used_bp', 'r.withdrawn_bp as withdrawn_bp', 'u.email as user_email', 'u.referral_link as referral_link',
                'u.id as user_id', 'r.id as referral_id'
            )
            ->get();
            
        if(!$referral && $referral->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'No referral  found'
            ], Response::HTTP_NOT_FOUND);
        }

        foreach ($referral as $referral_value) {
            $reffered = DB::table('users As u')
            ->leftJoin('referrals As r', function($join){
                $join->on('r.user_id', '=', 'u.id');
            })->where([
                ['referredby_user_id', '=', $referral_value->user_id]
            ])->select('u.email','u.referral_link','u.id', 'u.first_name', 'u.last_name', 'u.phone', 'r.id as referral_id')->get();

            $referral_value->downlines = $reffered;
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Refferal System',
            'data' => $referral
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

        $referral = DB::table('referrals As r')
            ->leftJoin('users As u', function($join){
                $join->on('r.user_id', '=', 'u.id');
            })
            ->where(
            [
                    ['r.user_id', '=', $request->user_id],
                    // ['u.referral_link', '!=', null]

                ]
            )
            ->select(
                'r.total_bp as total_bp', 'r.used_bp as used_bp', 'r.withdrawn_bp as withdrawn_bp', 'u.email as user_email', 'u.referral_link as referral_link',
                'u.id as user_id'
            )
            ->get();
            
            if(!$referral && $referral->isEmpty()){
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'status' => 'error',
                'message' => 'No referral  found'
            ], Response::HTTP_NOT_FOUND);
        }

        foreach ($referral as $referral_value) {
            $reffered = DB::table('users As u')
            ->leftJoin('referrals As r', function($join){
                $join->on('r.user_id', '=', 'u.id');
            })->where([
                ['referredby_user_id', '=', $referral_value->user_id]
            ])->select('u.email','u.referral_link','u.id', 'u.first_name', 'u.last_name', 'u.phone', 'r.id as referral_id')->get();

            $referral_value->downlines = $reffered;
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Refferal System',
            'data' => $referral
        ], Response::HTTP_OK);
    }

    

    /**
     * Show the form for creating a new resource.
     */
    public function create(CreateReferralRequest $request, Referral $referral)
    {
        // user
        $request->validated();

        $user = auth()->user();

        $ref_id = mt_rand(100000000, 999999999);

        $referral_user = $referral::where([
            ['user_id', '=', $user->id]
        ])->first();
        if(!$referral_user){
            $referral_user = $referral::create([
                'id' => (string)Str::uuid(),
                'user_id' => $user->id
            ]);
        }
        $referral_user->update(['ref_id' => $ref_id, 'payment_amount' => 5000]);

        $url = env('PAYSTACK_PAYMENT_INITIAL_URL', "https://api.paystack.co/transaction/initialize");

        $fields = [

            'email' => $user->email,

            'amount' => 5000 * 100,

            'callback_url' => env('APP_PAYMENT_REFERRAL_CALLBACK_URL', "http://localhost:8080/api/v1/referral/payment/callback"),

            'metadata' => [
                "referral_id" => $referral_user->id
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
        if($result['status'] === false){
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'status' => 'error',
                'message' => $result['message'],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $access_code = $result['data']['access_code'];
        $reference = $result['data']['reference'];

        $referral_user->update([
            'access_code' => $access_code,
            'reference' => $reference,
            'adminConsent' => null

        ]);

        $data = new Helpers();
        $referral_user->user = $data->user($referral_user->user_id);


        return response()->json(
            [
                'status_code' => Response::HTTP_CREATED,
                'status' => 'success',
                'message' => 'Referral Transaction Initiated',
                'data'=> [$referral_user, $result]
            ], Response::HTTP_CREATED
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReferralRequest $request, Referral $referral)
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

        $referral_id = $result['data']['metadata']['referral_id'];
        $payment_mode = $result['data']['channel'];
        $payment_status = $result['message'];

        $query_response = $referral::where(
            [
                ['id', '=', $referral_id],
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
                'message' => 'Payment Completed',
                'data'=> $query_response
            ], Response::HTTP_CREATED
        );
    }


    public function prove_payment(Referral $referral, Request $request)
    {
        // user
        $user = auth()->user();

        $payment = $referral::where('user_id', $user->id)->first();
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

    /**
     * Display the specified resource.
     */

    public function ushows(Referral $referral)
    {
        // user
        $user = auth()->user();

        $referral = DB::table('referrals As r')
            ->leftJoin('users As u', function($join){
                $join->on('r.user_id', '=', 'u.id');
            })
            ->where(
            [
                    ['u.id', '=', $user->id]
                ]
            )
            ->select(
                'r.total_bp as total_bp', 'r.used_bp as used_bp', 'r.withdrawn_bp as withdrawn_bp', 'u.email as user_email', 'u.referral_link as referral_link',
                'u.id as user_id', 'r.id as referral_id'
            )
            ->get();
            if($referral->isEmpty()){
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'status' => 'error',
                    'message' => 'No Refferal found'
                ], Response::HTTP_NOT_FOUND);
            }

        foreach ($referral as $referral_value) {

            $reffered = DB::table('users As u')
            ->leftJoin('referrals As r', function($join){
                $join->on('r.user_id', '=', 'u.id');
            })->where([
                ['referredby_user_id', '=', $referral_value->user_id]
            ])->select('u.email','u.referral_link','u.id', 'u.first_name', 'u.last_name', 'u.phone', 'r.id as referral_id')->get();

            $referral_value->downlines = $reffered;
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Referral Details',
            'data' => $referral
        ], Response::HTTP_ACCEPTED);
    }
   
   

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Referral $referral)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReferralRequest $request, Referral $referral)
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

        $query_response = $referral::where(
            [
                ['id', '=', $request->referral_id],
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

        $user_details = User::where(
            [
                ['id', '=', $query_response->user_id]
            ]
        )
        ->first();

        if($user_details){
            

            $referred_by_user = User::where(
                [
                    ['id', '=', $user_details->referredby_user_id],
                    ['activate', '=', 1],
                    ['verify_email', '=', 1]
                ]
            )
            ->first();

            if($referred_by_user){

                $cal_bonus = 5000 * (10 / 100);
                $cal_bonus_bp = $cal_bonus / $request->bprate;
                $new_referral_bp_balance = (float)$referred_by_user->referral_bp_balance + $cal_bonus_bp;

                $referred_by_user->update(['referral_bp_balance' => $new_referral_bp_balance]);

                $withdraw_user_ref = Referral::where(
                    [
                        ['user_id', '=', $user_details->referredby_user_id],
                        ['adminConsent', '=', 1],
                    ]
                )->first();
    
                if(!$withdraw_user_ref){
                    return response()->json([
                        'status_code' => Response::HTTP_NOT_FOUND,
                        'status' => 'error',
                        'message' => 'User not referred by anyone'
                    ], Response::HTTP_NOT_FOUND);
                }
                
                $total_bp = (float)$withdraw_user_ref->total_bp + $cal_bonus_bp;
                $withdraw_user_ref->update(['total_bp' => $total_bp]);
            }

            $referral_link = mt_rand(10000000, 99999999);
            
            
            $user_details->update([
                'referral_link' => $referral_link,
                'verify_email' => true,
                'verify_token' => 0,
                'email_verified_at' => now()
            ]); 
        }
        $query_response->update(['adminConsent' => 1]);

        $data = new Helpers();
        $query_response->user = $data->user($query_response->user_id);
        

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'status' => 'success',
            'message' => 'Referral Successful',
            'data' => $query_response
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Referral $referral)
    {
        //
    }
}