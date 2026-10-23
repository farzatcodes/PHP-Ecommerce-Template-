<?php
/**
 * Format a Bangladesh phone number for tel: links.
 * 01XXXXXXXXX → +8801XXXXXXXXX
 */
function bd_tel(string $phone): string {
    $digits = preg_replace('/\D/', '', trim($phone));
    if ($digits === '') return '';
    if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
        return '+880' . substr($digits, 1);
    }
    if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
        return '+880' . $digits;
    }
    if (str_starts_with($digits, '880')) {
        return '+' . $digits;
    }
    return '+' . $digits;
}

/**
 * Generate (and persist in session) a CSRF token.
 */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input field (echo directly into a form).
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Verify the CSRF token on POST — dies with 403 on mismatch.
 */
function csrf_verify(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $submitted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        http_response_code(403);
        die('Security token mismatch. Please go back and try again.');
    }
}


/**
 * Tailwind classes for an order-status pill (dot + tinted background).
 * Returns [pillClasses, dotClasses].
 */
function status_style(string $status): array {
    $map = [
        'Pending'    => ['bg-amber-50 text-amber-700 ring-amber-600/20',   'bg-amber-500'],
        'Processing' => ['bg-sky-50 text-sky-700 ring-sky-600/20',         'bg-sky-500'],
        'Hold'       => ['bg-slate-100 text-slate-700 ring-slate-500/20',  'bg-slate-400'],
        'Completed'  => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500'],
        'Cancelled'  => ['bg-rose-50 text-rose-700 ring-rose-600/20',      'bg-rose-500'],
    ];
    return $map[$status] ?? ['bg-slate-100 text-slate-700 ring-slate-500/20', 'bg-slate-400'];
}

/**
 * Render a status pill: <span> with a coloured dot and the status label.
 */
function status_pill(string $status): string {
    [$pill, $dot] = status_style($status);
    return '<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset '
         . $pill . '"><span class="h-1.5 w-1.5 rounded-full ' . $dot . '"></span>'
         . htmlspecialchars($status !== '' ? $status : 'Unknown') . '</span>';
}
