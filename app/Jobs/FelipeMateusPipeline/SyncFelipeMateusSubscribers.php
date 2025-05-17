<?php

namespace App\Jobs\FelipeMateusPipeline;

use App\User;
use App\Services\FelipeMateus\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Enums\StatusEnums;

class SyncFelipeMateusSubscribers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        User::chunk(
            100, function ($users) {
                foreach ($users as $user) {
                    try {
                        $subscriberId = $user->settings['felipemateus_subscriber_id'] ?? null;

                        // Se o usuário foi deletado, removemos ele da lista
                        if ($user->status === StatusEnums::DELETED) {
                            if ($subscriberId) {
                                EmailService::deleteSubscriber($user);
                                Log::info("Subscriber removido para o usuário ID {$user->id}");
                            }
                            continue;
                        }

                        // Todos os outros devem estar inscritos (com ou sem a tag) por causa dos emails enviados para tdoos usuarios.
                        if ($subscriberId) {
                            EmailService::updateSubscriber($user);
                            Log::info("Subscriber atualizado para o usuário ID {$user->id}");
                        } else {
                            EmailService::addSubscriber($user);
                            Log::info("Subscriber criado para o usuário ID {$user->id}");
                        }

                    } catch (\Throwable $e) {
                        Log::error("Erro ao sincronizar subscriber do usuário ID {$user->id}: " . $e->getMessage());
                    }
                }
            }
        );
    }

}
