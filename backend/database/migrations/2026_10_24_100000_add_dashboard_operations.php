<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category')->nullable()->after('sku');
            $table->decimal('sale_price', 12, 2)->nullable()->after('unit_weight_grams');
            $table->string('image_path')->nullable()->after('sale_price');
            $table->boolean('is_active')->default(true)->after('image_path');
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->string('store_location')->nullable()->after('name');
            $table->string('slot_label')->nullable()->after('store_location');
            $table->boolean('is_active')->default(true)->after('slot_label');
            $table->decimal('baseline_weight_grams', 8, 1)->nullable()->after('last_weight_grams');
            $table->decimal('threshold_grams', 8, 1)->nullable()->after('baseline_weight_grams');
            $table->string('shelf_status')->default('normal')->after('threshold_grams');
            $table->string('sensor_status')->default('unknown')->after('shelf_status');
            $table->timestamp('last_calibrated_at')->nullable()->after('sensor_status');
        });

        Schema::table('shelf_events', function (Blueprint $table) {
            $table->decimal('weight_before_grams', 8, 1)->nullable()->after('weight_grams');
            $table->string('staff_note', 500)->nullable()->after('match_note');
        });

        Schema::create('weight_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight_grams', 8, 1);
            $table->timestamp('recorded_at');
            $table->string('source', 32);
            $table->timestamps();

            $table->index(['device_id', 'recorded_at']);
        });

        Schema::create('processing_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('summary');
            $table->timestamps();
        });

        Schema::create('anomalies', function (Blueprint $table) {
            $table->id();
            $table->string('dedupe_key', 80)->unique();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shelf_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('processing_failure_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 40);
            $table->string('title');
            $table->text('summary');
            $table->string('priority', 20);
            $table->string('status', 20);
            $table->text('note')->nullable();
            $table->string('ignore_reason')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80);
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('summary', 500);
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('anomalies');
        Schema::dropIfExists('processing_failures');
        Schema::dropIfExists('weight_readings');

        Schema::table('shelf_events', function (Blueprint $table) {
            $table->dropColumn(['weight_before_grams', 'staff_note']);
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'store_location',
                'slot_label',
                'is_active',
                'baseline_weight_grams',
                'threshold_grams',
                'shelf_status',
                'sensor_status',
                'last_calibrated_at',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['category', 'sale_price', 'image_path', 'is_active']);
        });
    }
};
