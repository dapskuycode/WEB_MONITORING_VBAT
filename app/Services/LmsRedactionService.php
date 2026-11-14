<?php

namespace App\Services;

class LmsRedactionService
{
    private const REDACTED_KEYS = [
        'authorization',
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'push_token',
        'answers',
        'text_body',
        'correct_answer',
        'answer_aliases',
        'rubric',
        'signature',
    ];

    /**
     * Redact sensitive keys and strip bearer tokens / signed query parameters from strings.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function redactArray(array $payload): array
    {
        $sanitized = [];
        foreach ($payload as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if (in_array($normalizedKey, self::REDACTED_KEYS, true)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }
            if (is_array($value)) {
                $sanitized[$key] = $this->redactArray($value);

                continue;
            }
            if (is_string($value)) {
                $sanitized[$key] = $this->redactString($value);

                continue;
            }
            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    public function redactString(string $input): string
    {
        $redacted = preg_replace('/Bearer\s+[A-Za-z0-9\-\._~\+\/\|]+=*/i', 'Bearer [REDACTED]', $input) ?? $input;
        $redacted = preg_replace('/([?&]signature=)[A-Za-z0-9]+/i', '$1[REDACTED]', $redacted) ?? $redacted;

        return $redacted;
    }
}
