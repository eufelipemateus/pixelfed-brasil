<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Nomes reservados que não podem ser usados.
     *
     * @var array
     */
    protected $reservedUsernames = [
        'about',
        'help',
        'developer-api',
        'fediverse',
        'open-source',
        'banned-instances',
        'terms',
        'privacy',
        'platform',
        'language',
        'contact',
        'legal-notice',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->reservedUsernames as $reserved) {
            $users = DB::table('users')->where('username', $reserved)->get();

            foreach ($users as $user) {
                $newUsername = $this->generateUniqueUsername($reserved);
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => $newUsername]);
            }
        }
    }

    /**
     * Gera um nome de usuário único baseado no reservado.
     */
    protected function generateUniqueUsername(string $base): string
    {
        $suffix = 1;

        while (DB::table('users')->where('username', $base . '_reserved_' . $suffix)->exists()) {
            $suffix++;
        }

        return $base . '_reserved_' . $suffix;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        return;
    }
};
