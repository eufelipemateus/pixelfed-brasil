<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('purge_instance_id')->nullable()->index()->after('deleted_at');
            $table->uuid('purge_generation')->nullable()->after('purge_instance_id');
            $table->timestamp('purge_queued_at')->nullable()->index()->after('purge_generation');
            $table->unsignedSmallInteger('purge_retry_count')->default(0)->after('purge_queued_at');
            $table->text('purge_last_error')->nullable()->after('purge_retry_count');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'purge_instance_id',
                'purge_generation',
                'purge_queued_at',
                'purge_retry_count',
                'purge_last_error',
            ]);
        });
    }
};
