<?php

namespace App\Console\Commands;

use App\Jobs\InternalPipeline\DesactiveInactiveUserJob;
use Illuminate\Console\Command;

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
