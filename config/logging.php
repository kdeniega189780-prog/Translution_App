<?php

use Monolog\Handler\StreamHandler;

return ['default' => env('LOG_CHANNEL', 'single'), 'channels' => ['single' => ['driver' => 'single', 'path' => storage_path('logs/laravel.log'), 'level' => env('LOG_LEVEL', 'warning')], 'mail' => ['driver' => 'single', 'path' => storage_path('logs/mail.log'), 'level' => 'debug'], 'stderr' => ['driver' => 'monolog', 'handler' => StreamHandler::class, 'with' => ['stream' => 'php://stderr']]]];
