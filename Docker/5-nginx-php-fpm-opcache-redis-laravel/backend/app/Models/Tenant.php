<?php
// app/Models/Tenant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'tenant_code',
        'company_name',
        'legal_name',
        'email',
        'phone',
        'country_code',
        'owner_name',
        'owner_email',
        'owner_phone',
        'tax_number',
        'registration_number',
        'business_details',
        'current_plan_id',
        'subscription_status',
        'trial_ends_at',
        'subscription_ends_at',
        'max_companies',
        'max_users',
        'max_storage_mb',
        'api_access',
        'mobile_app_access',
        'settings',
        'features',
        'extensions',
        'custom_domain',
        'custom_domain_verified',
        'brand_color_primary',
        'brand_logo',
        'brand_favicon',
        'kyc_status',
        'kyc_documents',
        'kyc_verified_at',
        'is_active',
        'is_locked',
        'lock_reason',
        'last_active_at',
        'created_by'
    ];

    protected $casts = [
        'business_details' => 'array',
        'settings' => 'array',
        'features' => 'array',
        'extensions' => 'array',
        'kyc_documents' => 'array',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'kyc_verified_at' => 'datetime',
        'last_active_at' => 'datetime',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'custom_domain_verified' => 'boolean',
        'api_access' => 'boolean',
        'mobile_app_access' => 'boolean',
        'max_companies' => 'integer',
        'max_users' => 'integer',
        'max_storage_mb' => 'integer'
    ];

    protected $attributes = [
        'subscription_status' => 'trial',
        'kyc_status' => 'pending',
        'max_companies' => 1,
        'max_users' => 1,
        'max_storage_mb' => 1024
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            if (empty($tenant->tenant_code)) {
                $tenant->tenant_code = static::generateUniqueTenantCode();
            }
        });
    }

    /**
     * Relationships
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function currentPlan()
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->hasManyThrough(User::class, Employee::class, 'tenant_id', 'id', 'id', 'user_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_locked', false);
    }

    public function scopeWithActiveSubscription($query)
    {
        return $query->where('subscription_status', 'active')
                     ->orWhere(function ($q) {
                         $q->where('subscription_status', 'trial')
                           ->where('trial_ends_at', '>', now());
                     });
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('subscription_status', $status);
    }

    public function scopeByKycStatus($query, $status)
    {
        return $query->where('kyc_status', $status);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Accessors
     */
    public function getIsOnTrialAttribute(): bool
    {
        return $this->subscription_status === 'trial' && 
               $this->trial_ends_at && 
               $this->trial_ends_at->isFuture();
    }

    public function getIsSubscriptionActiveAttribute(): bool
    {
        return $this->subscription_status === 'active' || $this->is_on_trial;
    }

    public function getCompaniesCountAttribute(): int
    {
        return $this->companies()->count();
    }

    public function getEmployeesCountAttribute(): int
    {
        return $this->employees()->count();
    }

    public function getStorageUsedFormattedAttribute(): string
    {
        $used = $this->companies()->sum('storage_used_mb');
        return $this->formatBytes($used * 1024 * 1024);
    }

    public function getStoragePercentageAttribute(): float
    {
        $used = $this->companies()->sum('storage_used_mb');
        return round(($used / $this->max_storage_mb) * 100, 2);
    }

    public function getBrandLogoUrlAttribute(): ?string
    {
        return $this->brand_logo ? asset('storage/' . $this->brand_logo) : null;
    }

    public function getBrandFaviconUrlAttribute(): ?string
    {
        return $this->brand_favicon ? asset('storage/' . $this->brand_favicon) : null;
    }

    /**
     * Methods
     */
    public function canAddCompany(): bool
    {
        if ($this->max_companies === -1) return true; // Unlimited
        return $this->companies()->count() < $this->max_companies;
    }

    public function canAddUser(): bool
    {
        if ($this->max_users === -1) return true; // Unlimited
        return $this->employees()->count() < $this->max_users;
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];
        return in_array($feature, $features);
    }

    public function hasExtension(string $extension): bool
    {
        $extensions = $this->extensions ?? [];
        return in_array($extension, $extensions);
    }

    public function hasApiAccess(): bool
    {
        return $this->api_access && $this->is_subscription_active;
    }

    public function hasMobileAppAccess(): bool
    {
        return $this->mobile_app_access && $this->is_subscription_active;
    }

    public function canAccessFeature(string $feature): bool
    {
        return $this->is_subscription_active && $this->hasFeature($feature);
    }

    public function lock(string $reason = null): bool
    {
        return $this->update([
            'is_locked' => true,
            'lock_reason' => $reason
        ]);
    }

    public function unlock(): bool
    {
        return $this->update([
            'is_locked' => false,
            'lock_reason' => null
        ]);
    }

    public function verifyKyc(array $documents = null): bool
    {
        return $this->update([
            'kyc_status' => 'verified',
            'kyc_documents' => $documents,
            'kyc_verified_at' => now()
        ]);
    }

    public function rejectKyc(string $reason = null): bool
    {
        return $this->update([
            'kyc_status' => 'rejected',
            'kyc_documents' => array_merge($this->kyc_documents ?? [], ['rejection_reason' => $reason])
        ]);
    }

    public function updateLastActive(): bool
    {
        return $this->update(['last_active_at' => now()]);
    }

    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    public function setSetting(string $key, $value): self
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        return $this;
    }

    /**
     * Static Methods
     */
    public static function generateUniqueTenantCode(): string
    {
        do {
            $code = 'TNT_' . strtoupper(Str::random(8));
        } while (static::where('tenant_code', $code)->exists());

        return $code;
    }

    /**
     * Private Methods
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}