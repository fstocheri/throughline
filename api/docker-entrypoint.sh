#!/bin/sh
set -e

# Idempotent — safe to run on every boot, including every free-tier wake-from-sleep.
php artisan migrate --force

# Render's free tier has no Shell access, so unlike GreenStock (which seeds via a one-time
# manual shell command), first-boot data has to provision itself. Both of these are safe to
# run on every boot: demo:seed-once no-ops once the board has data, demo:ensure just
# idempotently upserts one user. `|| true` keeps either one from taking the whole container
# down if DEMO_EMAIL/DEMO_PASSWORD aren't set yet or a boot-time DB hiccup happens — better to
# come up in a not-yet-seeded state than not come up at all with no shell to fix it from.
php artisan demo:seed-once || true
php artisan demo:ensure || true

# Deliberately NOT using `php artisan serve` — on GreenStock it spawned the actual PHP
# built-in server as a *child* process that did not reliably inherit custom env vars
# (DB_CONNECTION/DB_URL silently fell back to config's sqlite default). Running the same
# router script directly as the foreground process sidesteps that indirection entirely.
#
# The router script resolves the public path via getcwd(), exactly like `artisan serve`
# relies on — so this has to run from public/, not the app root.
cd public
exec php -S 0.0.0.0:"${PORT:-8080}" ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
