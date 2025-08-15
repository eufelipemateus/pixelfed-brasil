<?php
namespace App\Util\ActivityPub\Contracts;

interface ActivityHandler
{
    public function __construct(array $headers, $profile, array $payload);

    public function handle(): void;
}
