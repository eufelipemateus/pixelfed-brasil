<?php

namespace App\Console\Commands\User;

use App\Enums\StatusEnums;
use App\Models\User;
use Illuminate\Console\Command;

class UserSuspend extends Command
{
    protected $signature = 'user:suspend {id}';

    protected $description = 'Suspend a local user.';

    public function handle(): int
    {
        $id = (string) $this->argument('id');
        $user = ctype_digit($id)
            ? User::find($id)
            : User::whereUsername($id)->first();

        if (! $user) {
            $this->error('Could not find any user with that username or id.');

            return self::FAILURE;
        }

        $this->info('Found user, username: '.$user->username);

        if (! $this->confirm('Are you sure you want to suspend this user?')) {
            return self::SUCCESS;
        }

        $user->status = StatusEnums::SUSPENDED;
        $user->profile->status = StatusEnums::SUSPENDED;
        $user->save();
        $user->profile->save();
        $this->info('User account has been suspended.');

        return self::SUCCESS;
    }
}
