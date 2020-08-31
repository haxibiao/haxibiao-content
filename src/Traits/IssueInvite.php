<?php

namespace Haxibiao\Content;

use App\Model;


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
    public function user(){
        return $this->belongsTo(\App\User::class,"user_id");
    }
    public function invited_user(){
        return $this->belongsTo(\App\User::class,"invite_user_id");
    }

    public function issue(){
        return $this->belongsTo(\App\Issue::class);
    }
}
