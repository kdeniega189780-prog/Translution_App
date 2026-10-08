<?php

namespace App\Http\Controllers;

use App\Application\Audit;
use App\Http\Requests\EmailRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetRequest;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $r)
    {
        $key = Str::lower($r->input('email')).'|'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }
        if (! Auth::attempt([...$r->safe()->only(['email', 'password']), 'active' => true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'The email or password is incorrect.']);
        }
        RateLimiter::clear($key);
        $r->session()->regenerate();
        $r->session()->put('session_version', $r->user()->session_version);

        return to_route('dashboard');
    }

    public function register(RegisterRequest $r)
    {
        $user = DB::transaction(function () use ($r) {
            $u = User::create([...$r->safe()->only(['name', 'email', 'mobile', 'password']), 'role' => 'customer']);
            Customer::create(['user_id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'mobile' => $u->mobile]);
            Audit::record($u, 'account.registered', $u);

            return $u;
        });
        Auth::login($user);
        $r->session()->regenerate();
        $r->session()->put('session_version', $user->session_version);

        return to_route('dashboard');
    }

    public function forgot(EmailRequest $r)
    {
        Password::sendResetLink($r->validated());

        return back()->with('status', 'If an account matches that email, a password reset link will be sent. It expires in 15 minutes.');
    }

    public function reset(ResetRequest $r)
    {
        $status = Password::reset($r->validated(), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60), 'must_change_password' => false, 'session_version' => $user->session_version + 1])->save();
            event(new PasswordReset($user));
            Audit::record($user, 'password.reset', $user);
        });

        return $status === Password::PASSWORD_RESET ? to_route('login')->with('status', 'Password reset. You can now sign in.') : back()->withErrors(['email' => 'This reset link is invalid or expired. Request a new link.']);
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return to_route('login');
    }
}
