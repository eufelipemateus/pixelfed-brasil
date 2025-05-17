<?php
/**
 * EmailService.php
 *
 * This file contains the EmailService class, which handles email-related operations.
 *
 * @category Services
 * @package  App\Services\FelipeMateus
 * @author   Felipe Mateus <eu@felipemateus.com>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://felipemateus.com
 */

namespace App\Services\FelipeMateus;

use App\User;
use App\UserSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Enums\FelipeMateusSubscribersTags;

/**
 * Class EmailService
 *
 * This service handles email-related operations.
 *
 * @category Services
 * @package  App\Services\FelipeMateus
 * @author   Felipe Mateus <eu@felipemateus.com>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://felipemateus.com
 */
class EmailService
{
    private const URL = 'https://sendportal.felipemateus.com/api/v1/subscribers';


    /**
     * Creates an HTTP client with the necessary headers and token.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private static function _client()
    {
        $token = config('felipemateus.sendportal.token');

        return Http::withToken($token)
            ->acceptJson()
            ->withHeaders(
                [
                'X-PASS' => 'sendportal',
                ]
            );
    }

    /**
     * Adiciona um novo assinante no SendPortal com base nos dados do usuário.
     *
     * @param User $user user data
     *
     * @return array|null
     */
    public static function addSubscriber(User $user): bool
    {
        $tags = [FelipeMateusSubscribersTags::PIXELFED->value()];

        if (!empty($user->settings['felipemateus_wants_updates'])) {
            $tags[] = FelipeMateusSubscribersTags::FELIPEMATEUS->value();
        }

        $response = self::_client()
            ->post(
                self::URL, [
                'first_name' => $user->name,
                'last_name'  => '',
                'email'      => $user->email,
                'tags'       => $tags,
                ]
            );

        if ($response->successful()) {
            $data = $response->json();
            $subscriberId = $data['data']['id'] ?? null;
            if ($subscriberId) {
                UserSetting::where("user_id", $user->id)
                ->update(
                    [
                        'felipemateus_subscriber_id' => $subscriberId
                    ]
                );
                return true;
            }
        }

        Log::error('Erro ao adicionar subscriber: ' . $response->body());
        return false;
    }

    /**
     * Atualiza um assinante existente no SendPortal.
     *
     * @param User $user user data
     *
     * @return array|null
     */
    public static function updateSubscriber(User $user): bool
    {
        $subscriberId = $user->settings['felipemateus_subscriber_id'] ?? null;

        if (!$subscriberId) {
            Log::warning("Usuário {$user->id} não possui subscriber_id para atualizar.");
            return false;
        }

        $tags = [FelipeMateusSubscribersTags::PIXELFED->value()];

        if (!empty($user->settings['felipemateus_wants_updates'])) {
            $tags[] =  FelipeMateusSubscribersTags::FELIPEMATEUS->value();
        }

        $response =self::_client()
            ->put(
                self::URL . '/' . $subscriberId, [
                'first_name' => $user->name,
                'last_name'  => '',
                'email'      => $user->email,
                'tags'       => $tags,
                ]
            );

        if ($response->successful()) {
            return true;
        }

        Log::error("Erro ao atualizar subscriber ID $subscriberId: " . $response->body());
        return false;
    }


    /**
     * Deleta um assinante existente no SendPortal.
     *
     * @param User $user user data
     *
     * @return bool
     */
    public static function deleteSubscriber(User $user): bool
    {
        $subscriberId = $user->settings['felipemateus_subscriber_id'] ?? null;

        if (!$subscriberId) {
            Log::warning("Usuário {$user->id} não possui subscriber_id para deletar.");
            return false;
        }

        $response = self::_client()
            ->delete(self::URL . '/' . $subscriberId);

        if ($response->successful()) {
            $user->settings = collect($user->settings)->except('felipemateus_subscriber_id')->all();
            $user->save();

            return true;
        }

        Log::error("Erro ao deletar subscriber ID $subscriberId: " . $response->body());
        return false;
    }
}
