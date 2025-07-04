<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\InternalPipeline\DesactiveInactiveUserJob;

class DesactiveInactiveAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:desactive-inactive-account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Desativa contas de usuários inativos por mais de 60 dias que não confirmaram o email e não foram excluídas e nunca fizeram login.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        DesactiveInactiveUserJob::dispatch()->onQueue('low');
    }
}
