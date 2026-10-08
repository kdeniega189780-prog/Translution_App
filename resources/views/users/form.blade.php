@extends('layouts.app')
@section('title', 'Team member')
@section('content')<div class="page-heading">
        <div>
            <h1>{{ $account->exists ? 'Edit team member' : 'Add team member' }}</h1>
            <p>Staff handle repairs. Purchasers handle incoming stock.</p>
        </div>
    </div>
    <section class="card form-card">
        <form method="post" action="{{ $account->exists ? route('users.update', $account) : route('users.store') }}">@csrf
            @if ($account->exists)
                @method('PUT')
            @endif
            <x-field name="name" label="Full name" :value="$account->name" maxlength="120" /><x-field name="email"
                label="Email address" type="email" :value="$account->email" /><x-field name="mobile" label="Mobile number"
                type="tel" :value="$account->mobile" />
            <div class="field"><label for="role">Role</label><select id="role" name="role">
                    @foreach (['secretary', 'mechanic', 'purchaser'] as $role)
                        <option value="{{ $role }}" @selected(old('role', $account->role) === $role)>{{ ucfirst($role) }}</option>
                    @endforeach
                </select></div><input type="hidden" name="active" value="0"><label class="toggle-row"><input
                    type="checkbox" name="active" value="1" @checked(old('active', $account->active))><span>Account
                    active<small>Switch off to prevent sign-in.</small></span></label><x-field name="password"
                label="Temporary password" type="password" autocomplete="new-password" :required="!$account->exists" data-strength
                hint="8+ characters, one uppercase letter, one number, and one symbol. Leave blank to keep the current password when editing." />
            <div class="strength"><meter min="0" max="4" value="0" id="password-strength"
                    aria-label="Password strength"></meter><small id="strength-label" aria-live="polite">Choose a strong
                    password</small></div><x-field name="password_confirmation" label="Confirm temporary password"
                type="password" autocomplete="new-password" :required="!$account->exists" />
            <div class="form-actions"><a href="{{ route('users.index') }}">Back</a><button class="button primary">Save team
                    member</button></div>
        </form>
</section>@endsection
