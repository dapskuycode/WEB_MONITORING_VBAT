<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class FcmAccessToken
{
    public function get(): string
    {
        $path = config('lms.fcm_credentials');
        if (! is_string($path) || ! is_file($path)) {
            throw new \RuntimeException('push_credentials_unavailable');
        }

        return Cache::remember('lms-fcm:'.hash('sha256', $path.':'.filemtime($path)), 3000, function () use ($path) {
            $credentials = new ServiceAccountCredentials('https://www.googleapis.com/auth/firebase.messaging', $path);
            $handler = HttpHandlerFactory::build(new Client(['timeout' => 15, 'connect_timeout' => 5]));
            $token = $credentials->fetchAuthToken($handler);
            if (! isset($token['access_token']) || ! is_string($token['access_token'])) {
                throw new \RuntimeException('push_authentication_failed');
            }

            return $token['access_token'];
        });
    }
}
