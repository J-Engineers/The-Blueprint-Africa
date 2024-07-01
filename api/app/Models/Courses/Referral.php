<?php

namespace App\Models\Courses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Referral extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'total_bp',
        'used_bp',
        'withdrawn_bp',
        'access_code',
        'reference',
        'ref_id',
        'payment_status',
        'payment_mode',
        'payment_amount',
        'prove_of_payment',
        'adminConsent',
        'referral_link'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'adminConsent' => 'boolean'
        ];
    }
}
