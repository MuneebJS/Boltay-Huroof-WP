<?php

defined('ABSPATH') || exit;

final class WPGP_Settings {
    public const OPTION_KEY = 'wpgp_settings';

    public static function init(): void {
        add_action('admin_init', [self::class, 'register']);
    }

    public static function ensure_defaults(): void {
        $defaults = self::defaults();
        $existing = get_option(self::OPTION_KEY);

        if (false === $existing) {
            add_option(self::OPTION_KEY, $defaults, '', 'no');
            return;
        }

        if (!is_array($existing)) {
            update_option(self::OPTION_KEY, $defaults, false);
            return;
        }

        $merged = array_merge($defaults, $existing);
        update_option(self::OPTION_KEY, $merged, false);
    }

    public static function register(): void {
        register_setting(
            'wpgp_settings_group',
            self::OPTION_KEY,
            [
                'type'              => 'array',
                'sanitize_callback' => [self::class, 'sanitize'],
                'default'           => self::defaults(),
            ]
        );
    }

    public static function sanitize($raw): array {
        $existing = get_option(self::OPTION_KEY, []);
        $existing = is_array($existing) ? $existing : [];
        $fallback = array_merge(self::defaults(), $existing);
        $raw = is_array($raw) ? $raw : [];

        return [
            'backend_base_url' => esc_url_raw($raw['backend_base_url'] ?? $fallback['backend_base_url']),
            'site_id'          => sanitize_text_field($raw['site_id'] ?? $fallback['site_id']),
            'project_id'       => sanitize_text_field(
                $raw['project_id'] ?? ($raw['workspace_id'] ?? $fallback['project_id'])
            ),
            'connection_id'    => sanitize_text_field($raw['connection_id'] ?? $fallback['connection_id']),
            'repo'             => sanitize_text_field($raw['repo'] ?? $fallback['repo']),
            'branch'           => sanitize_text_field($raw['branch'] ?? $fallback['branch']),
            'exclude_patterns' => WPGP_Security::sanitize_multiline_text($raw['exclude_patterns'] ?? $fallback['exclude_patterns']),
            'hmac_secret'      => sanitize_text_field($raw['hmac_secret'] ?? $fallback['hmac_secret']),
            'github_pat'       => sanitize_text_field($raw['github_pat'] ?? $fallback['github_pat']),
            'github_username'  => sanitize_text_field($raw['github_username'] ?? $fallback['github_username']),
            'last_job_id'      => sanitize_text_field($raw['last_job_id'] ?? $fallback['last_job_id']),
            'last_push_at'     => sanitize_text_field($raw['last_push_at'] ?? $fallback['last_push_at']),
            'last_pull_job_id' => sanitize_text_field($raw['last_pull_job_id'] ?? $fallback['last_pull_job_id']),
            'last_pull_at'     => sanitize_text_field($raw['last_pull_at'] ?? $fallback['last_pull_at']),
        ];
    }

    public static function get(): array {
        $value = get_option(self::OPTION_KEY, []);
        if (!is_array($value)) {
            return self::defaults();
        }

        return array_merge(self::defaults(), $value);
    }

    public static function update(array $new): void {
        $merged = array_merge(self::get(), $new);
        update_option(self::OPTION_KEY, self::sanitize($merged), false);
    }

    private static function defaults(): array {
        return [
            'backend_base_url' => '',
            'site_id'          => '',
            'project_id'       => '',
            'connection_id'    => '',
            'repo'             => '',
            'branch'           => 'main',
            'exclude_patterns' => implode(
                "\n",
                [
                    'wp-content/uploads/*',
                    '*.log',
                    '*.cache',
                    '.git/*',
                    'node_modules/*',
                ]
            ),
            'hmac_secret'      => '',
            'github_pat'       => '',
            'github_username'  => '',
            'last_job_id'      => '',
            'last_push_at'     => '',
            'last_pull_job_id' => '',
            'last_pull_at'     => '',
        ];
    }
}

