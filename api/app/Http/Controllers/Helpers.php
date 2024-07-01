<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\Courses\AcademyCourses;
use App\Models\Courses\PaymentDetails;
use App\Models\Courses\BlueprintCourses;

class Helpers extends Controller
{
    
    /**
     * Display a listing of the resource.
     */
    public function user($user_id)
    {
        $data = User::where([
            ['id', '=', $user_id]
        ])->select('first_name', 'last_name', 'email', 'phone', 'user_type')
        ->first();
        return $data;
    }

    public function user_referrals($user_id)
    {
        $data = User::where([
            ['referredby_user_id', '=', $user_id]
        ])->select('first_name', 'last_name', 'email', 'phone', 'user_type')
        ->get();
        return $data;
    }

    public function payment_details($payment_details_id)
    {
        $data = PaymentDetails::where([
            ['id', '=', $payment_details_id]
        ])->select('otp', 'account_name', 'bank_name', 'account_number')
        ->first();
        return $data;
    }

    public function academy_course($course_id)
    {
        $data = AcademyCourses::where([
            ['id', '=', $course_id]
        ])->select('name', 'description', 'outline_link', 'price', 'discount_rate', 'duration', 'image', 'referral_bonus_rate')
        ->first();
        return $data;
    }

    public function blueprint_course($course_id)
    {
        $data = BlueprintCourses::where([
            ['id', '=', $course_id]
        ])->select('name', 'description', 'outline_link', 'price', 'discount_rate', 'duration', 'image', 'referral_bonus_rate')
        ->first();
        return $data;
    }
}
