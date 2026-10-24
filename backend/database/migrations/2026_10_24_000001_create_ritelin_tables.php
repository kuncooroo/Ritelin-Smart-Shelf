<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->decimal('unit_weight_grams', 8, 1)->nullable();
            $table->timestamps();
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('firmware_version')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->decimal('last_weight_grams', 8, 1)->nullable();
            $table->timestamps();
        });

        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamp('occurred_at');
            $table->string('source')->default('simulator');
            $table->timestamps();
        });

        Schema::create('shelf_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('event_id');
            $table->string('event_type');
            $table->decimal('weight_grams', 8, 1);
            $table->timestamp('occurred_at');
            $table->string('match_status')->nullable();
            $table->string('resolution')->nullable();
            $table->string('match_note')->nullable();
            $table->foreignId('pos_transaction_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['device_id', 'event_id']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_events');
        Schema::dropIfExists('pos_transactions');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('products');
    }
};
