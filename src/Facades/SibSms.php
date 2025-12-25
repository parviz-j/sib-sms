<?php

namespace ParvizJ\SibSms\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \ParvizJ\SibSms\DTO\SendResult send(string $text, array $recipients, ?int $sender = null, ?int $userTraceId = null)
 * @method static \ParvizJ\SibSms\DTO\SendResult sendBulk(string $text, array $recipientsWithTrace, ?int $sender = null)
 * @method static \ParvizJ\SibSms\DTO\SendResult sendMultiple(array $items)
 * @method static \ParvizJ\SibSms\DTO\SendResult sendTokenSingle(string $templateKey, int|string $destination, array $params)
 * @method static \ParvizJ\SibSms\DTO\SendResult sendTokenMulti(string $templateKey, array $recipients)
 * @method static array tokenList()
 * @method static array statusById(array $ids)
 * @method static array statusByUserTraceId(array $traceIds)
 * @method static array accountInfo()
 */
class SibSms extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \ParvizJ\SibSms\Clients\SibSmsClient::class;
    }
}
