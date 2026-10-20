<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable()->index()->after('nodeinfo_last_fetched');
            $table->timestamp('purge_candidate_at')->nullable()->index()->after('last_active_at');
            $table->timestamp('purge_started_at')->nullable()->index()->after('purge_candidate_at');
            $table->uuid('purge_generation')->nullable()->after('purge_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            $table->dropColumn([
                'last_active_at',
                'purge_candidate_at',
                'purge_started_at',
                'purge_generation',
            ]);
        });
    }
};
