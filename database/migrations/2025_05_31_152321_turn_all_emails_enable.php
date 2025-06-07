<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //

        Schema::table('user_settings', function (Blueprint $table) {
            $table->boolean('send_email_new_follower')->default(true)->change();
            $table->boolean('send_email_new_follower_request')->default(true)->change();
            $table->boolean('send_email_on_share')->default(true)->change();
            $table->boolean('send_email_on_like')->default(true)->change();
            $table->boolean('send_email_on_mention')->default(true)->change();
            $table->boolean('felipemateus_wants_updates')->default(true)->change();
        });




    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
