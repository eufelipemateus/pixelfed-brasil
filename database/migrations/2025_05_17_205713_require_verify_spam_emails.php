<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $domains = [
            'email.com',
            'me.com',
            'icloud.com',
            'mac.com',
            'valmorbida.me',
            'bradtke.eu',
            'disroot.org',
            'duck.com',
            'tuta.io',
            'mailgw.com',
            'proton.me',
            'protonmail.com',
            'pm.me',
            'uorak.com',
            'ik.me',
            'tutamail.com',
            'protonmail.ch',
            'passmail.net',
            'hey.com'
        ];

        DB::table('users')
            ->where(function ($query) use ($domains) {
                foreach ($domains as $d) {
                    $query->orWhere('email', 'like', '%@' . $d);
                }
            })
            ->update([
                'email_verified_at' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
};
