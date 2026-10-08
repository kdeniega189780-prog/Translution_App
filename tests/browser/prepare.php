<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

// Runs only against a disposable SQLite file selected by smoke.mjs.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || config('database.default') !== 'sqlite' || ! str_ends_with(config('database.connections.sqlite.database'), 'browser.sqlite')) {
    throw new RuntimeException('Browser fixtures require the disposable test database.');
}
Artisan::call('migrate:fresh', ['--force' => true]);
Artisan::call('db:seed', ['--force' => true]);
foreach (User::all() as $user) {
    $user->update(['password' => 'BrowserFixture!42']);
}
echo "Disposable browser fixtures ready.\n";
