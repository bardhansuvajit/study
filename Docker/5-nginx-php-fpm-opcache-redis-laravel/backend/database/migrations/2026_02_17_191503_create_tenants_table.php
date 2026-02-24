<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();$table->string('tenant_code', 50)->unique(); // Unique identifier
            $table->string('company_name'); // Main company name
            $table->string('legal_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 20);
            $table->string('country_code', 3);
            
            // Owner/Contact Person
            $table->string('owner_name');
            $table->string('owner_email');
            $table->string('owner_phone', 20);
            
            // Business Details
            $table->string('tax_number')->nullable(); // GST/VAT
            $table->string('registration_number')->nullable(); // Company registration
            $table->json('business_details')->nullable(); // Additional business info
            
            // Subscription & Plan
            // $table->foreignId('current_plan_id')->nullable()->constrained('plans');
            $table->string('subscription_status')->default('trial'); // trial, active, suspended, cancelled
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            
            // Limits (denormalized for performance)
            $table->integer('max_companies')->default(1);
            $table->integer('max_users')->default(1);
            $table->bigInteger('max_storage_mb')->default(1024); // 1GB default
            $table->boolean('api_access')->default(false);
            $table->boolean('mobile_app_access')->default(false);
            
            // Settings & Configuration
            $table->json('settings')->nullable(); // tenant-specific settings
            $table->json('features')->nullable(); // enabled features
            $table->json('extensions')->nullable(); // installed extensions
            
            // White-labeling
            $table->string('custom_domain')->nullable();
            $table->boolean('custom_domain_verified')->default(false);
            $table->string('brand_color_primary')->nullable();
            $table->string('brand_logo')->nullable();
            $table->string('brand_favicon')->nullable();
            
            // KYC/Verification
            $table->string('kyc_status')->default('pending'); // pending, verified, rejected
            $table->json('kyc_documents')->nullable();
            $table->timestamp('kyc_verified_at')->nullable();
            
            // Status & Control
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false); // Admin lock
            $table->text('lock_reason')->nullable();
            $table->timestamp('last_active_at')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index(['subscription_status', 'is_active']);
            $table->index('custom_domain');
            $table->index('tenant_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
