<?php

namespace Haxibiao\Content;

use App\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueInvite extends Model
{
    public $fillable = [
        'user_id',
        'issue_id',
        'invite_user_id',
    ];

    public function freshTimestamp()
    {
        return time();
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, "user_id");
    }
    public function invited_user(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, "invite_user_id");
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(\App\Issue::class);
    }
}
