<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedInteger('physical_stock')->nullable()->after('last_calibrated_at');
            $table->unsignedInteger('pos_stock')->nullable()->after('physical_stock');
            $table->unsignedSmallInteger('stock_drop_units')->nullable()->after('pos_stock');
            $table->string('stock_status', 16)->default('unknown')->after('stock_drop_units');
            $table->timestamp('stock_watch_from')->nullable()->after('stock_status');
            $table->timestamp('stock_watch_until')->nullable()->after('stock_watch_from');
            $table->boolean('tare_requested')->default(false)->after('stock_watch_until');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'physical_stock',
                'pos_stock',
                'stock_drop_units',
                'stock_status',
                'stock_watch_from',
                'stock_watch_until',
                'tare_requested',
            ]);
        });
    }
};
