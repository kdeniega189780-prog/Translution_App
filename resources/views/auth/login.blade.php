@extends('layouts.auth')
@section('title', 'Sign in')
@section('content')<span class="eyebrow">WELCOME BACK</span>
    <h1>Let’s get you signed in.</h1>
    <p>One account. Everything you need for your day.</p>
    <form method="post" action="{{ route('login.store') }}">@csrf<x-field name="email" label="Email address" type="email"
            autocomplete="username" maxlength="255" /><x-field name="password" label="Password" type="password"
            autocomplete="current-password" /><a class="forgot-link" href="{{ route('password.request') }}">Forgot
            password?</a><button class="button primary full">Sign in <x-icon name="arrow" /></button></form>
<p class="auth-bottom">New customer? <a href="{{ route('register') }}">Create an account</a></p>@endsection
