<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class LmsDocumentInspection
{
    public function inspect(string $path): int
    {
        $this->runAntivirusIfConfigured($path);
        $info = new Process([config('lms.pdfinfo_binary'), '-box', $path]);
        $info->setTimeout(20);
        $info->run();
        if (! $info->isSuccessful() || ! preg_match('/^Pages:\s+(\d+)\s*$/m', $info->getOutput(), $matches)
            || ! preg_match('/^Encrypted:\s+no\s*$/m', $info->getOutput()) || (int) $matches[1] < 1 || (int) $matches[1] > 1000) {
            throw new \DomainException('invalid_or_encrypted_pdf');
        }

        return (int) $matches[1];
    }

    public function inspectImage(string $path): void
    {
        $this->runAntivirusIfConfigured($path);
        $head = @file_get_contents($path, false, null, 0, 8);
        if (! is_string($head) || (! str_starts_with($head, "\xFF\xD8\xFF") && ! str_starts_with($head, "\x89PNG\r\n\x1a\n"))) {
            throw new \DomainException('invalid_image');
        }
    }

    private function runAntivirusIfConfigured(string $path): void
    {
        $binary = trim((string) config('lms.clamscan_binary'));
        if ($binary === '') {
            if (app()->environment('local', 'testing') && config('lms.allow_unscanned_uploads') === true) {
                return;
            }
            throw new \RuntimeException('scanner_unavailable');
        }
        $scan = new Process([$binary, '--no-summary', '--', $path]);
        $scan->setTimeout(60);
        $scan->run();
        if ($scan->getExitCode() === 1) {
            throw new \DomainException('malware_detected');
        }
        if (! $scan->isSuccessful()) {
            throw new \RuntimeException('scanner_unavailable');
        }
    }
}
