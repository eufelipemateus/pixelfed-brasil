<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FelipeMateusPipeline\SyncFelipeMateusSubscribers;

class SyncFelipeMateus extends Command
{
    /**
     * O nome e a assinatura do comando.
     *
     * @var string
     */
    protected $signature = 'felipemateus:sync-subscribers';

    /**
     * A descrição do comando no Artisan.
     *
     * @var string
     */
    protected $description = 'Sincroniza todos os usuários com o SendPortal como assinantes.';

    /**
     * Executa o comando.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Disparando job de sincronização dos assinantes...');
        SyncFelipeMateusSubscribers::dispatch();
        $this->info('Job enfileirado com sucesso.');

        return Command::SUCCESS;
    }
}
