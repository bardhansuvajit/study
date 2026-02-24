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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();$table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Basic Information
            $table->string('company_code', 50)->unique();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('email');
            $table->string('phone', 20);
            $table->string('country_code', 3);
            
            // Business Niche/Category
            $table->string('primary_niche'); // ecommerce, edtech, travel, etc.
            $table->json('niches')->nullable(); // Multiple niches if applicable
            
            // Domain & Website
            $table->string('domain')->nullable(); // abc.com / xyz.com
            $table->boolean('domain_verified')->default(false);
            $table->string('subdomain')->nullable(); // abc.public-company.com
            $table->json('dns_records')->nullable(); // For custom domain setup
            
            // Address
            // $table->foreignId('country_code')->nullable()->constrained('countries');
            // $table->foreignId('state_id')->nullable()->constrained('states');
            // $table->foreignId('city_id')->nullable()->constrained('cities');
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('postal_code', 20)->nullable();
            
            // Business Details
            $table->string('tax_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('logo')->nullable();
            $table->json('social_links')->nullable();
            $table->json('payment_details')->nullable(); // Bank accounts, etc.
            
            // Company-specific settings
            $table->json('settings')->nullable();
            $table->json('features')->nullable(); // Enabled features for this company
            $table->json('extensions')->nullable(); // Extensions specific to this company
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            
            // Usage tracking
            $table->bigInteger('storage_used_mb')->default(0);
            $table->integer('api_calls_used')->default(0);
            $table->timestamp('last_api_call_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'is_active']);
            $table->index('domain');
            $table->index('subdomain');
            $table->index('primary_niche');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
