<?php
// =========================================================
// CSRF PROTECTION
// =========================================================

require_once __DIR__ . '/auth.php';

/**
 * Generate (or reuse) a CSRF token for the current session.
 */
function csrf_token(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Output a hidden input containing the CSRF token.
 */
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

/**
 * Validate a submitted CSRF token.
 */
function csrf_check(?string $submitted): bool {
    return !empty($submitted) && hash_equals(csrf_token(), $submitted);
}

/**
 * Validate and die with 403 if invalid.
 */
function csrf_require(?string $submitted) {
    if (!csrf_check($submitted)) {
        http_response_code(403);
        exit('Invalid request (CSRF).');
    }
}