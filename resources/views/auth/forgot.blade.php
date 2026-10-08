@extends('layouts.auth')
@section('title', 'Forgot password')
@section('content')<span class="eyebrow">LET’S HELP YOU BACK IN</span>
    <h1>Forgot your password?</h1>
    <p>Enter your email. We’ll send a reset link that expires in 15 minutes.</p>
    <form method="post" action="{{ route('password.email') }}">@csrf<x-field name="email" label="Email address" type="email"
            autocomplete="email" /><button class="button primary full">Send reset link <x-icon name="arrow" /></button>
    </form>
<p class="auth-bottom"><a href="{{ route('login') }}">Back to sign in</a></p>@endsection
