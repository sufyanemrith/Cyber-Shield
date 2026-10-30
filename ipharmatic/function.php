<?php
declare(strict_types=1);

/* HTML-escape dynamic values before printing them in HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* Validate a search term if a PHP results page processes the GET query. */
function clean_search(mixed $raw): ?string
{
    if (!is_string($raw)) {
        return null;
    }

    $term = trim($raw);
    if ($term === '') {
        return null;
    }

    $length = preg_match_all('/./us', $term);
    if ($length === false || $length > 80) {
        return null;
    }

    if (!preg_match("/\A[\p{L}\p{N}\s\-'.]+\z/u", $term)) {
        return null;
    }

    return $term;
}

/* Security headers for the PHP homepage. */
function send_security_headers(): void
{
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self'; "
        . "style-src 'self' https://fonts.googleapis.com; "
        . "font-src https://fonts.gstatic.com; "
        . "img-src 'self' data:; "
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
