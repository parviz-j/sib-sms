<?php

namespace ParvizJ\SibSms\DTO;

class SendResult
{
    public function __construct(
        public bool $ok,
        public ?int $messageId = null,      // result.id
        public ?int $userTraceId = null,    // result.userTraceId
        public array $raw = [],
        public ?string $error = null
    ) {}
}
