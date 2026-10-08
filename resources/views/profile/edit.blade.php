@extends('layouts.app')
@section('title', 'My Profile')
@section('content')<div class="page-heading">
        <div>
            <h1>My profile</h1>
            <p>{{ auth()->user()->must_change_password ? 'Welcome. Set your own password to continue.' : 'Keep your contact details and password up to date.' }}
            </p>
        </div>
    </div>
    <section class="card form-card">
        <p><strong>{{ auth()->user()->email }}</strong> · {{ ucfirst(auth()->user()->role) }}</p>
        <form method="post" action="{{ route('profile.update') }}">@csrf @method('PUT')<x-field name="name"
                label="Full name" :value="auth()->user()->name" maxlength="120" /><x-field name="mobile" label="Mobile number"
                type="tel" :value="auth()->user()->mobile" /><x-field name="current_password" label="Current password" type="password"
                autocomplete="current-password" /><x-field name="password" label="New password" type="password"
                autocomplete="new-password" :required="auth()->user()->must_change_password" data-strength
                hint="8+ characters, one uppercase letter, one number, and one symbol." />
            <div class="strength"><meter min="0" max="4" value="0" id="password-strength"
                    aria-label="Password strength"></meter><small id="strength-label" aria-live="polite">Choose a strong
                    password</small></div><x-field name="password_confirmation" label="Confirm new password" type="password"
                autocomplete="new-password" :required="auth()->user()->must_change_password" /><button class="button primary">Save profile</button>
        </form>
</section>@endsection
