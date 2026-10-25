<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anomalies', function (Blueprint $table) {
            $table->timestamp('checked_at')->nullable()->after('ignore_reason');
        });

        Schema::table('shelf_events', function (Blueprint $table) {
            $table->timestamp('checked_at')->nullable()->after('staff_note');
        });
    }

    public function down(): void
    {
        Schema::table('anomalies', function (Blueprint $table) {
            $table->dropColumn('checked_at');
        });

        Schema::table('shelf_events', function (Blueprint $table) {
            $table->dropColumn('checked_at');
        });
    }
};
