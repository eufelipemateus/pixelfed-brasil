<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFelipemateusFieldsToUserSettingsTable extends Migration
{
    public function up(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->string('felipemateus_subscriber_id')->nullable();
            $table->boolean('felipemateus_wants_updates')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn(['felipemateus_subscriber_id', 'felipemateus_wants_updates']);
        });
    }
}
