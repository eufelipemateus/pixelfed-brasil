<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('purge_instance_id')->nullable()->index()->after('deleted_at');
            $table->timestamp('purge_queued_at')->nullable()->index()->after('purge_instance_id');
            $table->unsignedSmallInteger('purge_retry_count')->default(0)->after('purge_queued_at');
            $table->text('purge_last_error')->nullable()->after('purge_retry_count');
        });

        // Use observed remote post activity as the historical signal. NodeInfo
        // fetch timestamps describe a local probe and must not be treated as
        // proof that the remote instance itself was active.
        DB::table('instances')
            ->whereNull('last_active_at')
            ->select(['id', 'domain'])
            ->orderBy('id')
            ->chunkById(100, function ($instances) {
                $activityByDomain = DB::table('profiles')
                    ->whereIn('domain', $instances->pluck('domain'))
                    ->whereNotNull('last_status_at')
                    ->where('last_status_at', '<=', now())
                    ->select('domain', DB::raw('MAX(last_status_at) as last_active_at'))
                    ->groupBy('domain')
                    ->pluck('last_active_at', 'domain');

                foreach ($instances as $instance) {
                    $lastActiveAt = $activityByDomain->get($instance->domain);

                    if ($lastActiveAt !== null) {
                        DB::table('instances')
                            ->where('id', $instance->id)
                            ->whereNull('last_active_at')
                            ->update(['last_active_at' => $lastActiveAt]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'purge_instance_id',
                'purge_queued_at',
                'purge_retry_count',
                'purge_last_error',
            ]);
        });
    }
};
