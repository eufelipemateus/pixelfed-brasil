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
        DB::table('users_labels')->insert(
            [
                [
                    'name' => 'popular',
                    'label' => 'Popular',
                    'description' => 'Esta conta é popular no  Pixelfed Brasil.',
                    'background_color' => '#FEF9C3',
                    'text_color' => '#CA8A04'
                ],
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
