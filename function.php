<?php
declare(strict_types=1);

/* 1. OUTPUT ENCODING TO REDUCE XSS RISK */
function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/* 2. VALIDATE MEDICINE SEARCH INPUT */
function clean_search(mixed $raw): ?string
{
    if (!is_string($raw)) {
        return null;
    }
    $q = trim($raw);
    
    // Count Unicode characters.
    $length = preg_match_all('/./us', $q);
    
    if (
        $q === '' ||
        $length === false ||
        $length > 80
    ) {
        return null;
    }
    
    // Allow letters, numbers, whitespace,
    // hyphens, apostrophes and periods.
    if (
        !preg_match(
            "/^[\p{L}\p{N}\s\-'.]+$/u",
            $q
        )
    ) {
        return null;
    }
    
    return $q;
}

/* 3. SECURITY HEADERS (Merged: Group's strict base + Cart's required exceptions) */
function send_security_headers(): void
{
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self'; "
        // Group's Google Fonts + Cart's Font Awesome
        . "style-src 'self' https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
        . "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; "
        // Group's local images + Cart's placeholder images
        . "img-src 'self' data: https://via.placeholder.com; "
        . "form-action 'self'; "
        . "base-uri 'self'; "
        . "frame-ancestors 'none'"
    );
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cache-Control: private, no-store');
    header_remove('X-Powered-By');
}