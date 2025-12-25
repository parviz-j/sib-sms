<?php

namespace ParvizJ\SibSms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsMessage extends Model
{
    protected $fillable = [
        'type','api_key_last4','sender','text',
        'provider_message_id','user_trace_id',
        'payload','raw_response','error'
    ];

    protected $casts = [
        'payload' => 'array',
        'raw_response' => 'array',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(SmsRecipient::class);
    }
}
