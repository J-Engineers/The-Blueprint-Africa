<?php

namespace App\Models\Courses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Subscribe extends Model
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
        'course_id',
        'ref_id',
        'access_code',
        'reference',
        'scholarship',
        'course_type',
        'bp_rate',
        'payment_amount',
        'prove_of_payment',
        'payment_mode',
        'payment_status',
        'adminConsent'
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
