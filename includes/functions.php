<?php
// =========================================================
// SHARED HELPER FUNCTIONS
// =========================================================

/**
 * Safe output escaping (HTML context).
 */
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Format a date for display.
 */
function format_date($value): string {
    if (empty($value)) return '—';
    $ts = is_numeric($value) ? $value : strtotime($value);
    return $ts ? date('M d, Y', $ts) : '—';
}

/**
 * Format a datetime for display.
 */
function format_datetime($value): string {
    if (empty($value)) return '—';
    $ts = is_numeric($value) ? $value : strtotime($value);
    return $ts ? date('M d, Y • h:i A', $ts) : '—';
}

/**
 * Status badge HTML.
 */
function status_badge(string $status): string {
    $map = [
        'open'      => ['Open',      'badge-warn'],
        'available' => ['Available', 'badge-info'],
        'matched'   => ['Matched',   'badge-blue'],
        'returned'  => ['Returned',  'badge-success'],
        'closed'    => ['Closed',    'badge-muted'],
    ];
    $info = $map[$status] ?? [$status, 'badge-muted'];
    return '<span class="badge ' . $info[1] . '">' . e($info[0]) . '</span>';
}

/**
 * Get the logo URL (uses picture/logo.jpg).
 */
function logo_url(): string {
    return BASE_URL . '/picture/logo.jpg';
}

/**
 * Get categories from DB.
 */
function get_categories(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Handle image upload. Returns stored path relative to uploads/ or null.
 */
function handle_upload(array $file): ?string {
    if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (error code ' . $file['error'] . ').');
    }
    $max = 4 * 1024 * 1024; // 4 MB
    if ($file['size'] > $max) {
        throw new RuntimeException('File is too large (max 4 MB).');
    }

    // Validate file extension
    $fileName = $file['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    
    if (!in_array($fileExt, $allowedExt)) {
        throw new RuntimeException('Only JPG, PNG, WEBP, and GIF images are allowed.');
    }

    // Validate real MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    
    if (!isset($allowedMimes[$mime])) {
        throw new RuntimeException('Invalid file type detected.');
    }

    // Verify extension matches MIME type
    $detectedExt = $allowedMimes[$mime];
    if ($detectedExt !== $fileExt && !($detectedExt === 'jpg' && $fileExt === 'jpeg')) {
        throw new RuntimeException('File extension does not match its content.');
    }

    // Generate secure filename
    $name = bin2hex(random_bytes(12)) . '.' . $detectedExt;
    $dest = UPLOAD_DIR . $name;

    // Ensure upload directory exists and is writable
    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0755, true);
    }

    // Add .htaccess protection if not exists
    $htaccess = UPLOAD_DIR . '.htaccess';
    if (!is_file($htaccess)) {
        file_put_contents($htaccess, 
            "Order deny,allow\nDeny from all\n<FilesMatch '\.(jpg|jpeg|png|webp|gif)$'>\nAllow from all\n</FilesMatch>"
        );
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }
    
    // Set proper permissions
    @chmod($dest, 0644);
    
    return $name; // stored as just the filename inside uploads/
}

/**
 * Delete an uploaded image.
 */
function delete_upload(?string $filename) {
    if (!$filename) return;
    $path = UPLOAD_DIR . $filename;
    if (is_file($path)) @unlink($path);
}

/**
 * Pagination helper. Returns [offset, totalPages, currentPage].
 */
function paginate(int $total, int $perPage, int $page): array {
    $perPage = max(1, $perPage);
    $pages   = max(1, (int)ceil($total / $perPage));
    $page    = max(1, min($page, $pages));
    $offset  = ($page - 1) * $perPage;
    return [$offset, $pages, $page];
}

/**
 * Build a query string preserving some keys.
 */
function qs(array $overrides = []): string {
    $qs = array_merge($_GET, $overrides);
    return http_build_query($qs);
}