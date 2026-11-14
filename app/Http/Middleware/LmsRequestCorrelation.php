<?php

namespace App\Http\Middleware;

use App\Services\LmsRedactionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LmsRequestCorrelation
{
    public function __construct(private LmsRedactionService $redactor) {}

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = trim((string) $request->header('X-Request-Id', ''));
        $requestId = ($incoming !== '' && preg_match('/^[A-Za-z0-9\-_]{8,64}$/', $incoming))
            ? $incoming
            : (string) Str::uuid();

        $request->attributes->set('lms_request_id', $requestId);
        Context::add('lms_request_id', $requestId);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        if ($response->getStatusCode() >= 400 && $request->is('api/v1/lms*', 'api/v1/admin/lms*')) {
            Log::info('lms.request.diagnostic', [
                'request_id' => $requestId,
                'method' => $request->method(),
                'path' => $this->redactor->redactString($request->getRequestUri()),
                'status' => $response->getStatusCode(),
                'user_id' => $request->user()?->id,
                'headers' => $this->redactor->redactArray([
                    'authorization' => $request->header('Authorization'),
                    'accept' => $request->header('Accept'),
                ]),
                'payload' => $this->redactor->redactArray($request->except(['file'])),
            ]);
        }

        return $response;
    }
}
