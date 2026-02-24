<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes;

    protected $appends = ['name'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'country_code',
        'phone_number',
        'alt_phone_number',
        'username',
        'password',
        'role',
        'status',
        'avatar',
        'last_login_at',
        'last_login_ip',
        'email_verified_at',
        'phone_verified_at',
        'notes',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'phone_verified_at'   => 'datetime',
            'last_login_at'       => 'datetime',
            'password'            => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'deleted_at'          => 'datetime',
        ];
    }

    // Map full_name → name in User model
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * Relationships
     */

    // Creator (admin who created the user)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Users created by this user
    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }
}
