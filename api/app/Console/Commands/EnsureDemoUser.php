<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureDemoUser extends Command
{
    protected $signature = 'demo:ensure';

    protected $description = 'Create or update the demo user from DEMO_EMAIL/DEMO_PASSWORD env vars';

    public function handle(): int
    {
        $email = env('DEMO_EMAIL');
        $password = env('DEMO_PASSWORD');

        if (! $email || ! $password) {
            $this->error('Set DEMO_EMAIL and DEMO_PASSWORD in the environment before running this.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $user->name ?: 'Demo User';
        $user->password = Hash::make($password);
        $user->email_verified_at ??= now();
        $user->save();

        $this->info("Demo user ready: {$email}");

        return self::SUCCESS;
    }
}
