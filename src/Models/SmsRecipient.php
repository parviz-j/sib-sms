<?php

namespace ParvizJ\SibSms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsRecipient extends Model
{
    protected $fillable = [
        'sms_message_id','destination','user_trace_id',
        'status_code','status_text','final_text'
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(SmsMessage::class, 'sms_message_id');
    }
}
