<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Progress ───────────────────────────────────────────────────────────────

function is_solved(string $lab_id): bool {
    return in_array($lab_id, $_SESSION['solved'] ?? [], true);
}

function mark_solved(string $lab_id): void {
    $_SESSION['solved'] ??= [];
    if (!in_array($lab_id, $_SESSION['solved'], true)) {
        $_SESSION['solved'][] = $lab_id;
    }
}

function solved_count(): int {
    return count($_SESSION['solved'] ?? []);
}

// ── Hints ──────────────────────────────────────────────────────────────────

function reveal_hint(string $lab_id, int $n): void {
    $_SESSION['hints'][$lab_id] ??= [];
    if (!in_array($n, $_SESSION['hints'][$lab_id], true)) {
        $_SESSION['hints'][$lab_id][] = $n;
    }
}

function hints_revealed(string $lab_id): array {
    return $_SESSION['hints'][$lab_id] ?? [];
}

// ── Payload log ────────────────────────────────────────────────────────────

function log_payload(string $lab_id, string $payload, string $context = ''): void {
    $_SESSION['payloads'][$lab_id] ??= [];
    array_unshift($_SESSION['payloads'][$lab_id], [
        'payload' => mb_substr($payload, 0, 500),
        'context' => $context,
        'ts'      => time(),
    ]);
    $_SESSION['payloads'][$lab_id] = array_slice($_SESSION['payloads'][$lab_id], 0, 30);
}

function get_payload_log(string $lab_id): array {
    return $_SESSION['payloads'][$lab_id] ?? [];
}

// ── Flag ───────────────────────────────────────────────────────────────────

function check_flag(string $submitted, string $flag_file): bool {
    $correct = trim(file_get_contents("/flags/{$flag_file}"));
    return trim($submitted) === $correct;
}

function flag_error_key(string $lab_id): string {
    return "flag_err_{$lab_id}";
}

// ── Render helpers ─────────────────────────────────────────────────────────

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function stars(int $n, int $max = 5): string {
    $out = '';
    for ($i = 1; $i <= $max; $i++) {
        $out .= "<span class=\"" . ($i <= $n ? 'star-filled' : 'star-empty') . "\">★</span>";
    }
    return $out;
}

function difficulty_badge(string $d): string {
    return "<span class=\"badge badge-{$d}\">{$d}</span>";
}

function topic_color(string $topic): string {
    return match ($topic) {
        'cmd'  => 'var(--orange)',
        'rce'  => 'var(--red)',
        'ssti' => 'var(--purple)',
        default => 'var(--text-muted)',
    };
}
