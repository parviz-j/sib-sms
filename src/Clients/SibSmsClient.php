<?php

namespace ParvizJ\SibSms\Clients;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use ParvizJ\SibSms\DTO\SendResult;
use ParvizJ\SibSms\Models\SmsMessage;
use ParvizJ\SibSms\Models\SmsRecipient;

class SibSmsClient
{
    public function __construct(private array $config) {}

    private function apiKey(): string
    {
        $key = (string)($this->config['api_key'] ?? '');
        if ($key === '') {
            throw new \RuntimeException('SIBSMS_API_KEY is not set.');
        }
        return $key;
    }

    private function base()
    {
        return Http::baseUrl(rtrim((string)$this->config['base_url'], '/'))
            ->timeout((int)($this->config['timeout'] ?? 20))
            ->acceptJson();
    }

    private function persist(): bool
    {
        return (bool)($this->config['persist'] ?? false);
    }

    private function storeRaw(): bool
    {
        return (bool)($this->config['store_raw'] ?? true);
    }

    private function storeMessage(string $type, array $payload, ?int $sender = null, ?string $text = null): ?SmsMessage
    {
        if (!$this->persist()) return null;

        return SmsMessage::create([
            'type' => $type,
            'api_key_last4' => substr($this->apiKey(), -4),
            'sender' => $sender,
            'text' => $text,
            'payload' => $payload,
        ]);
    }

    private function storeRecipients(?SmsMessage $msg, array $recipients): void
    {
        if (!$msg) return;

        foreach ($recipients as $r) {
            SmsRecipient::create([
                'sms_message_id' => $msg->id,
                'destination' => (string)($r['destination'] ?? $r['Destination'] ?? $r ?? ''),
                'user_trace_id' => $r['user_trace_id'] ?? $r['UserTraceId'] ?? null,
                'final_text' => $r['FinalText'] ?? null,
            ]);
        }
    }

    public function send(string $text, array $recipients, ?int $sender = null, ?int $userTraceId = null): SendResult
    {
        $sender = $sender ?? (int)($this->config['default_sender'] ?? 0);
        if ($sender <= 0) {
            throw new \InvalidArgumentException('Sender is required (set SIBSMS_DEFAULT_SENDER or pass $sender).');
        }

        $query = [
            'ApiKey' => $this->apiKey(),
            'Text' => $text,
            'Sender' => $sender,
            'Recipients' => implode(',', $recipients),
        ];
        if ($userTraceId !== null) $query['UserTraceId'] = $userTraceId;

        $msg = $this->storeMessage('send', $query, $sender, $text);

        try {
            $res = $this->base()->get('/Send', $query)->throw()->json();

            $id = (int) data_get($res, 'result.id', data_get($res, 'id'));
            $utr = data_get($res, 'result.userTraceId', data_get($res, 'userTraceId'));
            $utr = $utr !== null ? (int)$utr : null;

            if ($msg) {
                $msg->update([
                    'provider_message_id' => $id ?: null,
                    'user_trace_id' => $utr,
                    'raw_response' => $this->storeRaw() ? $res : null,
                ]);
                $this->storeRecipients($msg, array_map(fn($d) => ['destination' => $d], $recipients));
            }

            return new SendResult(true, $id ?: null, $utr, $res);
        } catch (RequestException $e) {
            $raw = $e->response?->json() ?? ['error' => $e->getMessage()];
            if ($msg) $msg->update(['error' => $e->getMessage(), 'raw_response' => $this->storeRaw() ? $raw : null]);
            return new SendResult(false, null, null, $raw, $e->getMessage());
        }
    }

    public function sendBulk(string $text, array $recipientsWithTrace, ?int $sender = null): SendResult
    {
        $sender = $sender ?? (int)($this->config['default_sender'] ?? 0);
        if ($sender <= 0) {
            throw new \InvalidArgumentException('Sender is required (set SIBSMS_DEFAULT_SENDER or pass $sender).');
        }

        $payload = [
            'ApiKey' => $this->apiKey(),
            'Text' => $text,
            'Sender' => $sender,
            'Recipients' => array_map(fn($r) => [
                'Destination' => (int)$r['destination'],
                'UserTraceId' => (int)$r['user_trace_id'],
            ], $recipientsWithTrace),
        ];

        $msg = $this->storeMessage('send_bulk', $payload, $sender, $text);

        try {
            $res = $this->base()->post('/SendBulk', $payload)->throw()->json();

            $id = (int) data_get($res, 'result.id', data_get($res, 'id'));
            $utr = data_get($res, 'result.userTraceId', data_get($res, 'userTraceId'));
            $utr = $utr !== null ? (int)$utr : null;

            if ($msg) {
                $msg->update([
                    'provider_message_id' => $id ?: null,
                    'user_trace_id' => $utr,
                    'raw_response' => $this->storeRaw() ? $res : null,
                ]);
                $this->storeRecipients($msg, $payload['Recipients']);
            }

            return new SendResult(true, $id ?: null, $utr, $res);
        } catch (RequestException $e) {
            $raw = $e->response?->json() ?? ['error' => $e->getMessage()];
            if ($msg) $msg->update(['error' => $e->getMessage(), 'raw_response' => $this->storeRaw() ? $raw : null]);
            return new SendResult(false, null, null, $raw, $e->getMessage());
        }
    }

    public function sendMultiple(array $items): SendResult
    {
        $payload = [
            'ApiKey' => $this->apiKey(),
            'Recipients' => array_map(fn($i) => [
                'Sender' => (int)($i['sender'] ?? $this->config['default_sender'] ?? 0),
                'Text' => (string)$i['text'],
                'Destination' => (int)$i['destination'],
                'UserTraceId' => (int)($i['user_trace_id'] ?? 0),
            ], $items),
        ];

        $msg = $this->storeMessage('send_multiple', $payload);

        try {
            $res = $this->base()->post('/SendMultiple', $payload)->throw()->json();

            $id = (int) data_get($res, 'result.id', data_get($res, 'id'));
            $utr = data_get($res, 'result.userTraceId', data_get($res, 'userTraceId'));
            $utr = $utr !== null ? (int)$utr : null;

            if ($msg) {
                $msg->update([
                    'provider_message_id' => $id ?: null,
                    'user_trace_id' => $utr,
                    'raw_response' => $this->storeRaw() ? $res : null,
                ]);
                $this->storeRecipients($msg, $payload['Recipients']);
            }

            return new SendResult(true, $id ?: null, $utr, $res);
        } catch (RequestException $e) {
            $raw = $e->response?->json() ?? ['error' => $e->getMessage()];
            if ($msg) $msg->update(['error' => $e->getMessage(), 'raw_response' => $this->storeRaw() ? $raw : null]);
            return new SendResult(false, null, null, $raw, $e->getMessage());
        }
    }

    public function sendTokenSingle(string $templateKey, int|string $destination, array $params): SendResult
    {
        $query = [
            'ApiKey' => $this->apiKey(),
            'TemplateKey' => $templateKey,
            'Destination' => $destination,
        ];

        foreach (array_values($params) as $idx => $val) {
            $query['p' . ($idx + 1)] = $val;
        }

        $msg = $this->storeMessage('send_token_single', $query);

        try {
            $res = $this->base()->get('/SendTokenSingle', $query)->throw()->json();

            $id = (int) data_get($res, 'result.id', data_get($res, 'id'));
            $utr = data_get($res, 'result.userTraceId', data_get($res, 'userTraceId'));
            $utr = $utr !== null ? (int)$utr : null;

            if ($msg) {
                $msg->update([
                    'provider_message_id' => $id ?: null,
                    'user_trace_id' => $utr,
                    'raw_response' => $this->storeRaw() ? $res : null,
                ]);
                $this->storeRecipients($msg, [['destination' => (string)$destination, 'user_trace_id' => $utr]]);
            }

            return new SendResult(true, $id ?: null, $utr, $res);
        } catch (RequestException $e) {
            $raw = $e->response?->json() ?? ['error' => $e->getMessage()];
            if ($msg) $msg->update(['error' => $e->getMessage(), 'raw_response' => $this->storeRaw() ? $raw : null]);
            return new SendResult(false, null, null, $raw, $e->getMessage());
        }
    }

    public function sendTokenMulti(string $templateKey, array $recipients): SendResult
    {
        $payload = [
            'ApiKey' => $this->apiKey(),
            'TemplateKey' => $templateKey,
            'Recipients' => array_map(fn($r) => [
                'Destination' => (int)$r['destination'],
                'UserTraceId' => (int)($r['user_trace_id'] ?? 0),
                'Parameters' => array_values($r['parameters'] ?? []),
            ], $recipients),
        ];

        $msg = $this->storeMessage('send_token_multi', $payload);

        try {
            $res = $this->base()->post('/SendTokenMulti', $payload)->throw()->json();

            $id = (int) data_get($res, 'result.id', data_get($res, 'id'));
            $utr = data_get($res, 'result.userTraceId', data_get($res, 'userTraceId'));
            $utr = $utr !== null ? (int)$utr : null;

            if ($msg) {
                $msg->update([
                    'provider_message_id' => $id ?: null,
                    'user_trace_id' => $utr,
                    'raw_response' => $this->storeRaw() ? $res : null,
                ]);
                $this->storeRecipients($msg, $payload['Recipients']);
            }

            return new SendResult(true, $id ?: null, $utr, $res);
        } catch (RequestException $e) {
            $raw = $e->response?->json() ?? ['error' => $e->getMessage()];
            if ($msg) $msg->update(['error' => $e->getMessage(), 'raw_response' => $this->storeRaw() ? $raw : null]);
            return new SendResult(false, null, null, $raw, $e->getMessage());
        }
    }

    public function tokenList(): array
    {
        return $this->base()->post('/TokenList', ['ApiKey' => $this->apiKey()])->throw()->json();
    }

    public function statusById(array $ids): array
    {
        return $this->base()->post('/StatusById', [
            'ApiKey' => $this->apiKey(),
            'Ids' => array_values($ids),
        ])->throw()->json();
    }

    public function statusByUserTraceId(array $traceIds): array
    {
        return $this->base()->post('/StatusByUserTraceId', [
            'ApiKey' => $this->apiKey(),
            'UserTraceIds' => array_values($traceIds),
        ])->throw()->json();
    }

    public function accountInfo(): array
    {
        return $this->base()->post('/AccountInfo', ['ApiKey' => $this->apiKey()])->throw()->json();
    }
}
