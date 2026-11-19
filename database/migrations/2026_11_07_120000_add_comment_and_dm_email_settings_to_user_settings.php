<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('user_settings', 'send_email_on_comment')) {
                $table->boolean('send_email_on_comment')->default(true);
            }
            if (! Schema::hasColumn('user_settings', 'send_email_on_dm')) {
                $table->boolean('send_email_on_dm')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            if (Schema::hasColumn('user_settings', 'send_email_on_comment')) {
                $table->dropColumn('send_email_on_comment');
            }
            if (Schema::hasColumn('user_settings', 'send_email_on_dm')) {
                $table->dropColumn('send_email_on_dm');
            }
        });
    }
};
