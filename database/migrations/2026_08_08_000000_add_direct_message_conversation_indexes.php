<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->index(['from_id', 'to_id', 'id'], 'direct_messages_from_to_id_index');
            $table->index(['to_id', 'is_hidden', 'from_id', 'id'], 'direct_messages_inbox_index');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['from_id', 'to_id', 'dm_id'], 'conversations_from_to_dm_index');
        });
    }

    public function down(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropIndex('direct_messages_from_to_id_index');
            $table->dropIndex('direct_messages_inbox_index');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_from_to_dm_index');
        });
    }
};
