@extends('layouts.app')
@section('title', 'Users')
@section('content')<div class="page-heading">
        <div>
            <h1>Your team & accounts</h1>
            <p>Give each person the access they need.</p>
        </div><a class="button primary" href="{{ route('users.create') }}"><x-icon name="plus" />Add team member</a>
    </div>
    <section class="card" id="users-list">
        {{ $users->links('pagination.simple', ['label' => 'Users']) }}
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name / Email</th>
                        <th>Role</th>
                        <th>Account</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $account)
                        <tr>
                            <td><strong>{{ $account->name }}</strong><small>{{ $account->email }}</small></td>
                            <td>{{ ucfirst($account->role) }}</td>
                            <td><x-badge :status="$account->active ? 'active' : 'inactive'" /></td>
                            <td>
                                @if ($account->hasRole('secretary', 'mechanic', 'purchaser'))
                                    <div class="inline-form"><a class="button compact"
                                            href="{{ route('users.edit', $account) }}">Edit access</a>
                                        <form method="post" action="{{ route('users.reset', $account) }}"
                                            data-confirm="Send a password reset link to this team member?">@csrf<button
                                                class="button compact">Send password reset</button></form>
                                </div>@else<span
                                        class="muted">{{ $account->role === 'admin' ? 'Owner account' : 'Customer account' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>{{ $users->links('pagination.simple', ['label' => 'Users']) }}
</section>@endsection
