<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $fillable = ['subject', 'body', 'sent_at', 'status'];

    public function groups() {
        return $this->belongsToMany(Group::class, 'email_group')->withTimestamps();
    }

    public function logs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmailLog::class);
    }
}
