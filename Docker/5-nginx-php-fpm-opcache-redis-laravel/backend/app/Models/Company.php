<?php
// app/Models/Company.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'company_code',
        'name',
        'legal_name',
        'email',
        'phone',
        'country_code',
        'primary_niche',
        'niches',
        'domain',
        'domain_verified',
        'subdomain',
        'dns_records',
        'country_id',
        'state_id',
        'city_id',
        'address_line1',
        'address_line2',
        'postal_code',
        'tax_number',
        'registration_number',
        'logo',
        'social_links',
        'payment_details',
        'settings',
        'features',
        'extensions',
        'is_active',
        'is_verified',
        'verified_at',
        'storage_used_mb',
        'api_calls_used',
        'last_api_call_at'
    ];

    protected $casts = [
        'niches' => 'array',
        'dns_records' => 'array',
        'social_links' => 'array',
        'payment_details' => 'array',
        'settings' => 'array',
        'features' => 'array',
        'extensions' => 'array',
        'domain_verified' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'last_api_call_at' => 'datetime',
        'storage_used_mb' => 'integer',
        'api_calls_used' => 'integer'
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->company_code)) {
                $company->company_code = static::generateUniqueCompanyCode();
            }
            if (empty($company->subdomain) && $company->tenant) {
                $company->subdomain = static::generateSubdomain($company->name, $company->tenant);
            }
        });
    }

    /**
     * Relationships
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'company_users', 'company_id', 'employee_id')
                    ->withPivot('job_title', 'department', 'is_primary_company', 'is_active')
                    ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_users', 'company_id', 'user_id')
                    ->withPivot('job_title', 'department', 'is_primary_company', 'is_active')
                    ->withTimestamps();
    }

    public function primaryEmployees()
    {
        return $this->employees()->wherePivot('is_primary_company', true);
    }

    public function activeUsers()
    {
        return $this->users()->wherePivot('is_active', true);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByNiche($query, $niche)
    {
        return $query->where('primary_niche', $niche)
                     ->orWhereJsonContains('niches', $niche);
    }

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeWithDomain($query, $domain)
    {
        return $query->where('domain', $domain)
                     ->orWhere('subdomain', $domain);
    }

    /**
     * Accessors
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->address_line1,
            $this->address_line2,
            $this->city?->name,
            $this->state?->name,
            $this->country?->name,
            $this->postal_code
        ];

        return implode(', ', array_filter($parts));
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getWebsiteUrlAttribute(): ?string
    {
        if ($this->domain) {
            return 'https://' . $this->domain;
        }
        if ($this->subdomain) {
            return 'https://' . $this->subdomain;
        }
        return null;
    }

    public function getStorageUsedFormattedAttribute(): string
    {
        return $this->formatBytes($this->storage_used_mb * 1024 * 1024);
    }

    public function getIsDomainConfiguredAttribute(): bool
    {
        return !empty($this->domain) || !empty($this->subdomain);
    }

    public function getAllNichesAttribute(): array
    {
        $niches = $this->niches ?? [];
        if (!in_array($this->primary_niche, $niches)) {
            array_unshift($niches, $this->primary_niche);
        }
        return $niches;
    }

    /**
     * Methods
     */
    public function verify(): bool
    {
        return $this->update([
            'is_verified' => true,
            'verified_at' => now()
        ]);
    }

    public function verifyDomain(): bool
    {
        return $this->update(['domain_verified' => true]);
    }

    public function addDnsRecord(string $type, string $name, string $value): self
    {
        $records = $this->dns_records ?? [];
        $records[] = [
            'type' => $type,
            'name' => $name,
            'value' => $value,
            'created_at' => now()->toIso8601String()
        ];
        $this->dns_records = $records;
        
        return $this;
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

    public function canAccessFeature(string $feature): bool
    {
        return $this->is_active && 
               $this->tenant->is_subscription_active && 
               $this->hasFeature($feature);
    }

    public function trackApiCall(): bool
    {
        return $this->update([
            'api_calls_used' => $this->api_calls_used + 1,
            'last_api_call_at' => now()
        ]);
    }

    public function updateStorageUsed(): int
    {
        // Calculate storage from related models (products, documents, etc.)
        // This would be implemented based on your specific storage tracking needs
        $total = 0;
        
        // Example: $total += $this->products()->sum('image_size');
        // Example: $total += $this->documents()->sum('file_size');
        
        $this->storage_used_mb = ceil($total / (1024 * 1024));
        $this->save();
        
        return $this->storage_used_mb;
    }

    public function isWithinStorageLimit(int $additionalBytes = 0): bool
    {
        $currentUsage = $this->storage_used_mb * 1024 * 1024;
        $newUsage = $currentUsage + $additionalBytes;
        $maxStorage = $this->tenant->max_storage_mb * 1024 * 1024;
        
        return $newUsage <= $maxStorage;
    }

    /**
     * Static Methods
     */
    public static function generateUniqueCompanyCode(): string
    {
        do {
            $code = 'CMP_' . strtoupper(Str::random(8));
        } while (static::where('company_code', $code)->exists());

        return $code;
    }

    public static function generateSubdomain(string $companyName, Tenant $tenant): string
    {
        $base = Str::slug($companyName);
        $subdomain = $base;
        $counter = 1;
        
        while (static::where('subdomain', $subdomain . '.' . config('app.main_domain'))->exists()) {
            $subdomain = $base . '-' . $counter;
            $counter++;
        }
        
        return $subdomain . '.' . config('app.main_domain');
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