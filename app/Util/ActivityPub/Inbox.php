<?php

namespace App\Util\ActivityPub;

use Illuminate\Support\Facades\Log;
use Throwable;

class Inbox
{
    protected $headers;

    protected $profile;

    protected $payload;

    protected $logger;

    public static $handlers = [];

    public function __construct($headers, $profile, $payload)
    {
        $this->headers = $headers;
        $this->profile = $profile;
        $this->payload = $payload;
    }

    public function handle()
    {
        $this->handleVerb();
    }

    public function handleVerb()
    {
        $type = (string) ($this->payload['type'] ?? '');
        $handlerClass = static::$handlers[$type] ?? null;

        if ($handlerClass && class_exists($handlerClass)) {
            try {
                $handler = new $handlerClass($this->headers, $this->profile, $this->payload);
                $handler->handle();
            } catch (\Throwable $e) {
                Log::error("[ActivityPub][$type] handler error: {$e->getMessage()}");
            }
        } else {
            Log::debug("[ActivityPub] No handler found for activity type: {$type}");
        }
    }

    public static function registerHandler(string $type, string $handlerClass): void
    {
        static::$handlers[$type] = $handlerClass;
    }


    public static function registerHandlers(array $handlers): void
    {
        foreach ($handlers as $type => $class) {
            static::registerHandler($type, $class);
        }
    }

    public function actorFirstOrCreate($actorUrl)
    {
        return Helpers::profileFetch($actorUrl);
    }

    public function handlePollCreate()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }
        $url = isset($activity['url']) ? $activity['url'] : $activity['id'];
        Helpers::statusFirstOrFetch($url);
    }
}
