@extends('layouts.auth')
@section('title', 'Create account')
@section('content')<span class="eyebrow">LET’S GET STARTED</span>
    <h1>Your car care, connected.</h1>
    <p>Create your customer account to book and track repairs.</p>
    <form method="post" action="{{ route('register.store') }}">@csrf<x-field name="name" label="Full name"
            autocomplete="name" maxlength="120" /><x-field name="email" label="Email address" type="email"
            autocomplete="email" /><x-field name="mobile" label="Mobile number" type="tel"
            autocomplete="tel" /><x-field name="password" label="Password" type="password" autocomplete="new-password"
            data-strength hint="8+ characters, one uppercase letter, one number, and one symbol." />
        <div class="strength"><meter min="0" max="4" value="0" id="password-strength"
                aria-label="Password strength"></meter><small id="strength-label" aria-live="polite">Choose a strong
                password</small></div><x-field name="password_confirmation" label="Confirm password" type="password"
            autocomplete="new-password" /><button class="button primary full">Create account <x-icon
                name="arrow" /></button>
    </form>
<p class="auth-bottom">Already registered? <a href="{{ route('login') }}">Sign in</a></p>@endsection
