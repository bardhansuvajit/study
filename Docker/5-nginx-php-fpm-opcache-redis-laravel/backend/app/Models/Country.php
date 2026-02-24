<?php
// app/Models/Country.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'phone_code',
        'phone_no_digits',
        'zip_code_format',
        'currency_code',
        'currency_symbol',
        'continent',
        'flag',
        'language',
        'time_zone',
        'shipping_availability',
        'cash_on_delivery_availability',
        'is_active'
    ];

    protected $casts = [
        'shipping_availability' => 'boolean',
        'cash_on_delivery_availability' => 'boolean',
        'is_active' => 'boolean',
        'phone_no_digits' => 'integer'
    ];

    /**
     * Relationships
     */
    public function states()
    {
        return $this->hasMany(State::class, 'country_code', 'code');
    }

    public function companies()
    {
        return $this->hasMany(Company::class, 'country_code', 'code');
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'country_code', 'code');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByContinent($query, $continent)
    {
        return $query->where('continent', $continent);
    }

    public function scopeWithShipping($query)
    {
        return $query->where('shipping_availability', true);
    }

    /**
     * Accessors
     */
    public function getLanguageArrayAttribute(): array
    {
        return explode(',', $this->language);
    }

    public function getFormattedPhoneCodeAttribute(): string
    {
        return '+' . $this->phone_code;
    }

    public function getFlagUrlAttribute(): ?string
    {
        return $this->flag ? asset('storage/' . $this->flag) : null;
    }

    /**
     * Methods
     */
    public function getPhoneNumberFormat(string $phone): string
    {
        return $this->phone_code . $phone;
    }

    public function validatePhoneNumber(string $phone): bool
    {
        if (!$this->phone_no_digits) {
            return true;
        }
        
        return strlen($phone) === $this->phone_no_digits;
    }

    public function formatZipCode(string $zip): string
    {
        if (!$this->zip_code_format) {
            return $zip;
        }

        // Simple zip code formatting logic
        $format = str_replace('#', '%s', $this->zip_code_format);
        return sprintf($format, ...str_split($zip));
    }
}