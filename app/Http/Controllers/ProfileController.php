<?php

namespace App\Http\Controllers;

use App\Application\Audit;
use App\Http\Requests\ProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit');
    }

    public function update(ProfileRequest $r)
    {
        DB::transaction(function () use ($r) {
            $u = $r->user();
            $data = $r->safe()->only(['name', 'mobile']);
            if ($r->filled('password')) {
                $data += ['password' => $r->password, 'must_change_password' => false, 'session_version' => $u->session_version + 1, 'remember_token' => Str::random(60)];
            } $u->update($data);
            $u->customer?->update(['name' => $u->name, 'mobile' => $u->mobile]);
            $r->session()->put('session_version', $u->session_version);
            $r->session()->regenerate();
            Audit::record($u, 'profile.updated', $u);
        });

        return to_route('dashboard')->with('status', 'Profile saved.');
    }

    public function notifications(Request $r)
    {
        $r->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Notifications marked as read.');
    }
}
