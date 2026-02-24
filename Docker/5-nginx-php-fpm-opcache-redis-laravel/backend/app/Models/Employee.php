<?php
// app/Models/Employee.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_code',
        'user_id',
        'tenant_id',
        'primary_company_id',
        'department',
        'designation',
        'joining_date',
        'exit_date',
        'employment_type',
        'salary',
        'salary_currency',
        'compensation_details',
        'date_of_birth',
        'gender',
        'emergency_contact_name',
        'emergency_contact_phone',
        'blood_group',
        'bank_name',
        'bank_account_number',
        'bank_ifsc_code',
        'pan_number',
        'documents',
        'is_active'
    ];

    protected $casts = [
        'compensation_details' => 'array',
        'documents' => 'array',
        'joining_date' => 'date',
        'exit_date' => 'date',
        'date_of_birth' => 'date',
        'salary' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    protected $attributes = [
        'employment_type' => 'full_time',
        'salary_currency' => 'INR'
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            if (empty($employee->employee_code)) {
                $employee->employee_code = static::generateUniqueEmployeeCode();
            }
        });

        static::created(function ($employee) {
            // Assign default role for new employee
            // This would be implemented based on your role system
        });
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function primaryCompany()
    {
        return $this->belongsTo(Company::class, 'primary_company_id');
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_users', 'employee_id', 'company_id')
                    ->withPivot('job_title', 'department', 'is_primary_company', 'is_active')
                    ->withTimestamps();
    }

    public function roles()
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles')
                    ->withPivot('company_id', 'tenant_id')
                    ->withTimestamps();
    }

    public function permissions()
    {
        return $this->morphToMany(Permission::class, 'model', 'model_has_permissions')
                    ->withPivot('company_id', 'tenant_id')
                    ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByEmploymentType($query, $type)
    {
        return $query->where('employment_type', $type);
    }

    public function scopeCurrent($query)
    {
        return $query->whereNull('exit_date')
                     ->orWhere('exit_date', '>', now());
    }

    /**
     * Accessors
     */
    public function getFullNameAttribute(): string
    {
        return $this->user?->full_name ?? '';
    }

    public function getEmailAttribute(): string
    {
        return $this->user?->email ?? '';
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->user?->phone ?? null;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function getYearsOfServiceAttribute(): ?int
    {
        return $this->joining_date?->diffInYears(now());
    }

    public function getIsCurrentAttribute(): bool
    {
        return !$this->exit_date || $this->exit_date->isFuture();
    }

    public function getFormattedSalaryAttribute(): string
    {
        if (!$this->salary) {
            return 'N/A';
        }
        
        $symbol = $this->salary_currency === 'INR' ? '₹' : '$';
        return $symbol . ' ' . number_format($this->salary, 2);
    }

    public function getDocumentUrlsAttribute(): array
    {
        $urls = [];
        foreach ($this->documents ?? [] as $key => $path) {
            $urls[$key] = asset('storage/' . $path);
        }
        return $urls;
    }

    /**
     * Methods
     */
    public function assignToCompany(Company $company, array $pivotData = []): void
    {
        $defaultData = [
            'job_title' => $this->designation,
            'department' => $this->department,
            'is_primary_company' => $this->companies()->count() === 0,
            'is_active' => true
        ];

        $this->companies()->syncWithoutDetaching([
            $company->id => array_merge($defaultData, $pivotData)
        ]);
    }

    public function removeFromCompany(Company $company): void
    {
        $this->companies()->detach($company->id);
    }

    public function setPrimaryCompany(Company $company): bool
    {
        // Remove primary flag from all companies
        $this->companies()->updateExistingPivot($this->companies->pluck('id'), [
            'is_primary_company' => false
        ]);

        // Set new primary company
        return $this->companies()->updateExistingPivot($company->id, [
            'is_primary_company' => true
        ]);
    }

    public function hasRole($role, Company $company = null): bool
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->first();
        }

        if (!$role) {
            return false;
        }

        return $this->roles()
                    ->where('role_id', $role->id)
                    ->when($company, function ($query) use ($company) {
                        $query->where('company_id', $company->id);
                    })
                    ->exists();
    }

    public function hasPermission($permission, Company $company = null): bool
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->first();
        }

        if (!$permission) {
            return false;
        }

        // Check direct permissions
        $hasDirect = $this->permissions()
                          ->where('permission_id', $permission->id)
                          ->when($company, function ($query) use ($company) {
                              $query->where('company_id', $company->id);
                          })
                          ->exists();

        if ($hasDirect) {
            return true;
        }

        // Check through roles
        foreach ($this->roles()->when($company, fn($q) => $q->where('company_id', $company->id))->get() as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function terminate(?string $exitDate = null): bool
    {
        return $this->update([
            'exit_date' => $exitDate ?: now(),
            'is_active' => false
        ]);
    }

    public function addDocument(string $type, $file): string
    {
        $path = $file->store('employee-documents/' . $this->id, 'public');
        
        $documents = $this->documents ?? [];
        $documents[$type] = $path;
        $this->documents = $documents;
        $this->save();
        
        return $path;
    }

    public function updateCompensation(string $type, float $amount): self
    {
        $details = $this->compensation_details ?? [];
        $details[$type] = $amount;
        $this->compensation_details = $details;
        
        return $this;
    }

    /**
     * Static Methods
     */
    public static function generateUniqueEmployeeCode(): string
    {
        do {
            $code = 'EMP_' . strtoupper(Str::random(8));
        } while (static::where('employee_code', $code)->exists());

        return $code;
    }
}