<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_spots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('parking_type')->nullable();
            $table->string('access_type')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('osm_id')->nullable()->unique();
            $table->string('osm_fee')->nullable();
            $table->string('osm_charge')->nullable();
            $table->string('city')->nullable();
            $table->string('source')->default('OSM');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['lat', 'lng']);
            $table->index('city');
        });

        Schema::create('occupancy_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parking_spot_id');
            $table->string('status', 16);
            $table->string('device_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('parking_spot_id')->references('id')->on('parking_spots')->cascadeOnDelete();
            $table->index(['parking_spot_id', 'expires_at']);
            $table->index(['device_id', 'created_at']);
        });

        Schema::create('fee_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('city');
            $table->string('kind', 32);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('unit', 16)->default('HOUR');
            $table->text('source_note');
            $table->date('effective_from');
            $table->uuid('parking_spot_id')->nullable();
            $table->timestamps();

            $table->foreign('parking_spot_id')->references('id')->on('parking_spots')->nullOnDelete();
            $table->index(['city', 'kind']);
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('label')->nullable();
            $table->string('group')->default('apis');
            $table->boolean('is_secret')->default(true);
            $table->timestamps();
        });

        Schema::create('ingest_cache', function (Blueprint $table) {
            $table->string('cache_key')->primary();
            $table->timestamp('fetched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingest_cache');
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('fee_rules');
        Schema::dropIfExists('occupancy_reports');
        Schema::dropIfExists('parking_spots');
    }
};
