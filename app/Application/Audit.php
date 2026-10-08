<?php

namespace App\Application;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Audit
{
    public static function record(?User $actor, string $action, Model $subject, array $details = []): void
    {
        ActivityLog::create(['user_id' => $actor?->id, 'action' => $action, 'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->id, 'details' => $details]);
    }
}
