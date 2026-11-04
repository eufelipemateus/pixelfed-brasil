<?php

namespace App\Observers;

use App\Models\Instance;
use App\Services\InstanceModerationService;
use App\Services\InstanceService;
use Illuminate\Support\Facades\Schema;

class InstanceObserver
{
    public function creating(Instance $instance): void
    {
        if ($this->rulesTableExists()) {
            InstanceModerationService::applyPersistedRule($instance);
        }

        if (
            config('federation.new_instances_unlisted')
            && ! array_key_exists('unlisted', $instance->getAttributes())
        ) {
            $instance->unlisted = true;
        }
    }

    public function created(Instance $instance): void
    {
        if ($this->rulesTableExists()) {
            InstanceModerationService::sync($instance);
        }

        // Column defaults (e.g. unlisted) are only known to the DB, so always flush.
        InstanceService::forgetModeratedDomains();
    }

    public function updated(Instance $instance): void
    {
        if (! $instance->wasChanged(['banned', 'unlisted', 'auto_cw', 'notes'])) {
            return;
        }

        if ($this->rulesTableExists()) {
            InstanceModerationService::sync($instance);
        }

        InstanceService::forgetModeratedDomains();
    }

    public function deleted(Instance $instance): void
    {
        InstanceService::forgetModeratedDomains();
    }

    public function deleting(Instance $instance): void
    {
        if ($this->rulesTableExists()) {
            InstanceModerationService::sync($instance);
        }
    }

    private function rulesTableExists(): bool
    {
        return Schema::hasTable('instance_moderation_rules');
    }
}
