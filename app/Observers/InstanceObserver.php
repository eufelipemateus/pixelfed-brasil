<?php

namespace App\Observers;

use App\Models\Instance;
use App\Services\InstanceModerationService;
use Illuminate\Support\Facades\Schema;

class InstanceObserver
{
    public function creating(Instance $instance): void
    {
        if ($this->rulesTableExists()) {
            InstanceModerationService::applyPersistedRule($instance);
        }
    }

    public function created(Instance $instance): void
    {
        if ($this->rulesTableExists()) {
            InstanceModerationService::sync($instance);
        }
    }

    public function updated(Instance $instance): void
    {
        if (
            $this->rulesTableExists()
            && $instance->wasChanged(['banned', 'unlisted', 'auto_cw', 'notes'])
        ) {
            InstanceModerationService::sync($instance);
        }
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
