<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');

            // Contact Information
            $table->string('email')->unique();
            $table->string('country_code', 3);
            $table->string('phone_number', 15);
            $table->string('alt_phone_number', 15);

            // Admin-specific fields
            $table->string('username')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'moderator', 'user'])->default('user');

            // Additional useful fields
            $table->text('avatar')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();

            // Verification and Security
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->rememberToken();

            // Metadata
            $table->text('notes')->nullable(); // For admin notes
            $table->foreignId('created_by')->nullable()->constrained('users');

            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->softDeletes();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['role', 'status']);
            $table->index('username');
            $table->index('phone_number');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        $data = [
            'full_name' => 'Base Admin',
            'email' => 'admin@example.com',
            'country_code' => 'IN',
            'phone_number' => '1234567890',
            'alt_phone_number' => '',
            'username' => 'base_admin',
            'password' => Hash::make('secret'),
            'role' => 'admin',
            'status' => 'active',
        ];

        DB::table('users')->insert($data);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
