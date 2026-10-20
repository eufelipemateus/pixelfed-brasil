<?php

namespace App\Console\Commands;

use App\Models\Instance;
use App\Services\InstancePurgeService;
use Illuminate\Console\Command;

class AuditInactiveInstances extends Command
{
    protected $signature = 'instances:audit-inactive {--limit=100 : Máximo de registros detalhados}';

    protected $description = 'Audita instâncias antigas que ainda não têm evidências suficientes para limpeza automática';

    public function handle(): int
    {
        $threshold = InstancePurgeService::threshold();
        $limit = min(1000, max(1, (int) $this->option('limit')));
        $old = Instance::query()->where('created_at', '<', $threshold);

        $summary = [
            ['candidatas confirmadas', InstancePurgeService::inactiveInstancesQuery($threshold)->count()],
            ['sem atividade federada observada', (clone $old)->whereNull('last_active_at')->count()],
            ['sem NodeInfo observado', (clone $old)->whereNull('nodeinfo_last_fetched')->count()],
            ['sem timeout de entrega', (clone $old)->where('delivery_timeout', false)->count()],
            ['com falhas abaixo do limite', (clone $old)->where('delivery_failures', '<', config('federation.activitypub.delivery.failure_threshold', 5))->count()],
        ];

        $this->info("Auditoria de instâncias anteriores a {$threshold->toDateTimeString()}");
        $this->table(['categoria', 'total'], $summary);

        $rows = (clone $old)
            ->where(function ($query) use ($threshold) {
                $query->whereNull('last_active_at')
                    ->orWhere('last_active_at', '>=', $threshold)
                    ->orWhereNull('nodeinfo_last_fetched')
                    ->orWhere('nodeinfo_last_fetched', '>=', $threshold)
                    ->orWhere('delivery_timeout', false)
                    ->orWhere('delivery_failures', '<', config('federation.activitypub.delivery.failure_threshold', 5));
            })
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'domain', 'last_active_at', 'nodeinfo_last_fetched', 'delivery_timeout', 'delivery_failures'])
            ->map(fn (Instance $instance) => [
                $instance->id,
                $instance->domain,
                $instance->last_active_at?->toDateTimeString() ?? 'desconhecida',
                $instance->nodeinfo_last_fetched?->toDateTimeString() ?? 'desconhecido',
                $instance->delivery_timeout ? 'sim' : 'não',
                $instance->delivery_failures,
            ]);

        if ($rows->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['id', 'domínio', 'última atividade', 'último NodeInfo', 'timeout', 'falhas'],
                $rows
            );
        }

        return self::SUCCESS;
    }
}
