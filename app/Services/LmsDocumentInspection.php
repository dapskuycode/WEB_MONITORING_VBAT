<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class LmsDocumentInspection
{
    public function inspect(string $path): int
    {
        $scan = new Process([config('lms.clamscan_binary'), '--no-summary', '--', $path]);
        $scan->setTimeout(60);
        $scan->run();
        if ($scan->getExitCode() === 1) {
            throw new \DomainException('malware_detected');
        }
        if (! $scan->isSuccessful()) {
            throw new \RuntimeException('scanner_unavailable');
        }
        $info = new Process([config('lms.pdfinfo_binary'), '-box', $path]);
        $info->setTimeout(20);
        $info->run();
        if (! $info->isSuccessful() || ! preg_match('/^Pages:\s+(\d+)\s*$/m', $info->getOutput(), $matches)
            || ! preg_match('/^Encrypted:\s+no\s*$/m', $info->getOutput()) || (int) $matches[1] < 1 || (int) $matches[1] > 1000) {
            throw new \DomainException('invalid_or_encrypted_pdf');
        }

        return (int) $matches[1];
    }
}
