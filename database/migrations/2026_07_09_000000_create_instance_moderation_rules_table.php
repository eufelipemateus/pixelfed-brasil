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
        Schema::create('instance_moderation_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('domain')->unique();
            $table->boolean('banned')->default(false)->index();
            $table->boolean('unlisted')->default(false)->index();
            $table->boolean('auto_cw')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instance_moderation_rules');
    }
};
