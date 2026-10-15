<?php
// =========================================================
// FLASH MESSAGES (one-time notifications)
// =========================================================
if (session_status() === PHP_SESSION_NONE) session_start();

function flash_set(string $type, string $message) {
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}
function flash_success(string $m) { flash_set('success', $m); }
function flash_error(string $m)   { flash_set('error',   $m); }
function flash_info(string $m)    { flash_set('info',    $m); }

function flash_render() {
    if (empty($_SESSION['_flash'])) return;
    foreach ($_SESSION['_flash'] as $f) {
        $cls = match ($f['type']) {
            'success' => 'flash-success',
            'error'   => 'flash-error',
            default   => 'flash-info',
        };
        echo '<div class="flash ' . $cls . '">' .
             '<span>' . htmlspecialchars($f['message'], ENT_QUOTES) . '</span>' .
             '<button type="button" class="flash-close" onclick="this.parentElement.remove()">×</button>' .
             '</div>';
    }
    unset($_SESSION['_flash']);
}