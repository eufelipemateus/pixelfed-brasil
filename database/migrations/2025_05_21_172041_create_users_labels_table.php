<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users_labels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('description')->nullable();
            $table->string('background_color')->default('#000000');
            $table->string('text_color')->default('#000000');
        });

        DB::table('users_labels')->insert([
            [
                'name' => 'admin',
                'label' => 'Admin',
                'description' => 'This Account is an administrator of the platform.',
                'background_color' => '#FEE2E2',
                'text_color' => '#B91C1C'
            ],
            [
                'name' => 'mod',
                'label' => 'Moderator',
                'description' => 'This Account is a moderator of the platform.',
                'background_color' => '#E0F2FE',
                'text_color' => '#1D4ED8'
            ],
            [
                'name' => 'new',
                'label' => 'New',
                'description' => 'This Account is new to the platform.',
                'background_color' => '#dcfce7',
                'text_color' => '#15803d'
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_labels');
    }
};
