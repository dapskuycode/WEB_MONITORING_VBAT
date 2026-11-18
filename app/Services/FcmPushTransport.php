<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FcmPushTransport
{
    public function __construct(private FcmAccessToken $credentials) {}

    /** @param array<string, string> $data */
    public function send(string $token, string $title, string $body, array $data): string
    {
        $project = config('lms.fcm_project_id');
        if (! is_string($project) || ! preg_match('/^[a-z][a-z0-9-]{4,62}$/', $project)) {
            throw new \RuntimeException('push_not_configured');
        }
        $response = Http::withToken($this->credentials->get())->acceptJson()->timeout(15)->connectTimeout(5)
            ->post('https://fcm.googleapis.com/v1/projects/'.$project.'/messages:send', [
                'message' => ['token' => $token, 'notification' => ['title' => $title, 'body' => $body], 'data' => $data],
            ]);
        if ($response->successful() && is_string($response->json('name')) && str_starts_with($response->json('name'), 'projects/'.$project.'/messages/')) {
            return $response->json('name');
        }
        foreach ($response->json('error.details', []) as $detail) {
            if (($detail['@type'] ?? '') === 'type.googleapis.com/google.firebase.fcm.v1.FcmError' && ($detail['errorCode'] ?? '') === 'UNREGISTERED') {
                throw new \DomainException('push_token_unregistered');
            }
        }
        // Provider responses can echo tokens; persist only a bounded, non-sensitive failure code.
        throw new \RuntimeException('push_provider_http_'.$response->status());
    }
}
