<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use Illuminate\Support\Str;

class GenerateReferCodes extends Command
{
    protected $signature = 'users:generate-refer-codes';
    protected $description = 'Gera códigos de referência únicos para usuários que ainda não possuem um';

    public function handle()
    {
        $users = User::whereNull('refer_code')->get();

        $this->info("Encontrados {$users->count()} usuários sem refer_code.");

        $updated = 0;

        foreach ($users as $user) {
            do {
                $code = strtoupper(Str::random(8));
            } while (User::where('refer_code', $code)->exists());

            $user->refer_code = $code;
            $user->save();

            $updated++;
        }

        $this->info("Códigos gerados para {$updated} usuários.");
        return 0;
    }
}
