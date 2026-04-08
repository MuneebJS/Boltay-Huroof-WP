<?php

defined('ABSPATH') || exit;

final class WPGP_Security {
    public static function ensure_admin(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this action.', 'wp-github-push'));
        }
    }

    public static function verify_nonce(string $key, string $action): void {
        $nonce = sanitize_text_field($_POST[$key] ?? '');
        if (!wp_verify_nonce($nonce, $action)) {
            wp_die(esc_html__('Security check failed.', 'wp-github-push'));
        }
    }

    public static function sanitize_multiline_text($value): string {
        $value = is_string($value) ? $value : '';
        $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
        $lines = array_map('sanitize_text_field', $lines);
        $lines = array_filter($lines, static fn ($line) => '' !== trim((string) $line));
        return implode("\n", $lines);
    }

    public static function sign_payload(string $payload, string $secret, int $timestamp): string {
        $message = $timestamp . '.' . $payload;
        return hash_hmac('sha256', $message, $secret);
    }

    public static function generate_idempotency_key(): string {
        return wp_generate_uuid4();
    }
}

