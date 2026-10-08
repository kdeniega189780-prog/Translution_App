@extends('layouts.auth')
@section('title', 'Reset password')
@section('content')<h1>Choose a new password.</h1>
    <form method="post" action="{{ route('password.update') }}">@csrf<input type="hidden" name="token"
            value="{{ $token }}"><x-field name="email" label="Email address" type="email" :value="request('email')"
            autocomplete="username" /><x-field name="password" label="New password" type="password"
            autocomplete="new-password" data-strength
            hint="8+ characters, one uppercase letter, one number, and one symbol." />
        <div class="strength"><meter min="0" max="4" value="0" id="password-strength"
                aria-label="Password strength"></meter><small id="strength-label" aria-live="polite">Choose a strong
                password</small></div><x-field name="password_confirmation" label="Confirm password" type="password"
            autocomplete="new-password" /><button class="button primary full">Reset password</button>
</form>@endsection
