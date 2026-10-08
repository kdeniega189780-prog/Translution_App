<?php

namespace App\Http\Controllers;

use App\Application\Audit;
use App\Http\Requests\ListRequest;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    public function index(ListRequest $r)
    {
        return view('users.index', ['users' => User::orderBy('role')->orderBy('name')->orderBy('id')->paginate(config('workshop.table_page_size'))->withQueryString()->fragment('users-list')]);
    }

    public function create()
    {
        return view('users.form', ['account' => new User(['active' => true, 'role' => 'secretary'])]);
    }

    public function store(UserRequest $r)
    {
        DB::transaction(function () use ($r) {
            $u = User::create([...$r->safe()->except('password_confirmation'), 'must_change_password' => true]);
            Audit::record($r->user(), 'user.created', $u);
        });

        return to_route('users.index')->with('status', 'Account created. Share the temporary password privately.');
    }

    public function edit(User $user)
    {
        abort_unless($user->hasRole('secretary', 'mechanic', 'purchaser'), 403);

        return view('users.form', ['account' => $user]);
    }

    public function update(UserRequest $r, User $user)
    {
        abort_unless($user->hasRole('secretary', 'mechanic', 'purchaser'), 403);
        DB::transaction(function () use ($r, $user) {
            $data = $r->safe()->except('password_confirmation');
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['must_change_password'] = true;
            } $data['session_version'] = $user->session_version + 1;
            $user->update($data);
            Audit::record($r->user(), 'user.edited', $user, ['role' => $user->role, 'active' => $user->active]);
        });

        return to_route('users.index')->with('status', 'Account updated. Previous sessions have been revoked.');
    }

    public function reset(Request $r, User $user)
    {
        abort_unless($user->hasRole('secretary', 'mechanic', 'purchaser'), 403);
        Password::sendResetLink(['email' => $user->email]);
        Audit::record($r->user(), 'user.reset_requested', $user);

        return back()->with('status', 'Password reset requested. The link expires in 15 minutes.');
    }
}
