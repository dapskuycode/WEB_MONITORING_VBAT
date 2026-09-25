<?php

namespace App\Services;

use App\Models\InfoContent;
use Illuminate\Support\Str;

class InfoContentService
{
    public static function getWhatsAppCTA(InfoContent $content, bool $isPremium): ?string
    {
        // CTA hanya untuk premium
        if (!$isPremium || !$content->is_premium || !$content->whatsapp_number) {
            return null;
        }

        return self::generateWhatsAppLink($content->whatsapp_number, $content->title);
    }

    private static function generateWhatsAppLink(string $number, string $title): string
    {
        // Normalize number: remove +, spaces, dashes
        $cleanNumber = preg_replace('/[+\s\-]/', '', $number);

        $message = "Halo, saya tertarik dengan: {$title}";

        return "https://wa.me/{$cleanNumber}?text=" . urlencode($message);
    }

    public static function isWhatsAppConfigured(string $number): bool
    {
        // Server-side config check (tidak hardcode mobile)
        $allowedNumbers = config('whatsapp.numbers', []);

        return in_array($number, $allowedNumbers);
    }
}
