<?php
// app/Models/State.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class State extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'country_id',
        'country_code',
        'code',
        'name',
        'tax_rate',
        'is_active'
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Relationships
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function cities()
    {
        return $this->hasMany(City::class);
    }

    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCountry($query, $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeWithTaxRate($query, $rate = null)
    {
        if ($rate) {
            return $query->where('tax_rate', $rate);
        }
        return $query->where('tax_rate', '>', 0);
    }

    /**
     * Accessors
     */
    public function getFullNameAttribute(): string
    {
        return $this->name . ', ' . $this->country->name;
    }

    public function getFormattedTaxRateAttribute(): string
    {
        return $this->tax_rate . '%';
    }

    /**
     * Methods
     */
    public function calculateTax($amount): float
    {
        return ($amount * $this->tax_rate) / 100;
    }
}