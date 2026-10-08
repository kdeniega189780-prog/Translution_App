<?php

use App\Application\Audit;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('workshop:create-admin {email} {name}', function () {
    if (! filter_var($this->argument('email'), FILTER_VALIDATE_EMAIL)) {
        $this->error('Enter a valid email.');

        return 1;
    }
    if (User::where('email', $this->argument('email'))->exists()) {
        $this->error('This email is already registered.');

        return 1;
    }
    $password = Str::password(20);
    $u = User::create(['email' => $this->argument('email'), 'name' => $this->argument('name'), 'mobile' => 'Not set', 'role' => 'admin', 'password' => $password, 'must_change_password' => true]);
    Audit::record($u, 'admin.created', $u);
    $this->info('Temporary password (store privately): '.$password);
    $this->info('The first sign-in requires a password change and a mobile number.');
})->purpose('Create the initial owner account with a generated temporary password');
