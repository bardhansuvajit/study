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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();

            $table->string('code', 2)->unique();
            $table->string('name')->unique();
            $table->string('phone_code');
            $table->integer('phone_no_digits')->nullable();
            $table->string('zip_code_format')->nullable()->comment('#:number');
            $table->string('currency_code', 3)->comment('ISO 4217 code');
            $table->string('currency_symbol', 20);
            $table->string('continent');
            $table->text('flag')->nullable();
            $table->string('language')->comment('comma separated');
            $table->string('time_zone');

            $table->boolean('shipping_availability')->default(1);
            $table->boolean('cash_on_delivery_availability')->default(0);

            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
