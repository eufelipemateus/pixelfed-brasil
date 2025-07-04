<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('pages')
            ->where('slug', '/site/about')
            ->update(['slug' => '/about']);

        DB::table('pages')
            ->where('slug', '/site/privacy')
            ->update(['slug' => '/privacy']);


        DB::table('pages')
            ->where('slug', '/site/terms')
            ->update(['slug' => '/terms']);


        DB::table('pages')
            ->where('slug', '/site/kb/community-guidelines')
            ->update(['slug' => '/kb/community-guidelines']);


        DB::table('pages')
            ->where('slug', '/site/legal-notice')
            ->update(['slug' => '/legal-notice']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pages')
            ->where('slug', '/about')
            ->update(['slug' => '/site/about']);

        DB::table('pages')
            ->where('slug', '/privacy')
            ->update(['slug' => '/site/privacy']);


        DB::table('pages')
            ->where('slug', '/terms')
            ->update(['slug' => '/site/terms']);

        DB::table('pages')
            ->where('slug', '/kb/community-guidelines')
            ->update(['slug' => '/site/kb/community-guidelines']);

        DB::table('pages')
            ->where('slug', '/legal-notice')
            ->update(['slug' => '/site/legal-notice']);
    }
};
