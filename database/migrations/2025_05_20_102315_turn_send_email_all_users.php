<?php

use Illuminate\Database\Migrations\Migration;
use App\UserSetting;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        UserSetting::query()->update(
            [
                'send_email_new_follower' => true,
                'send_email_new_follower_request' => true,
                'send_email_on_share' =>  true,
                'send_email_on_like' => true,
                'send_email_on_mention' => true,
                'felipemateus_wants_updates' => true,
            ]
        );

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
