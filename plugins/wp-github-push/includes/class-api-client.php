<?php

defined('ABSPATH') || exit;

final class WPGP_API_Client {
    private const LOG_TRANSIENT = 'wpgp_api_debug_log';
    private const LOG_MAX_ENTRIES = 50;

    public static function get_debug_log(): array {
        return get_transient(self::LOG_TRANSIENT) ?: [];
    }

    public static function clear_debug_log(): void {
        delete_transient(self::LOG_TRANSIENT);
    }

    private static function log_api_call(string $method, string $url, $request_body, int $status, $response_body, float $duration_ms): void {
        $log = self::get_debug_log();
        array_unshift($log, [
            'time'          => gmdate('Y-m-d H:i:s') . ' UTC',
            'method'        => $method,
            'url'           => $url,
            'request_body'  => is_string($request_body) ? mb_substr($request_body, 0, 4000) : mb_substr((string) wp_json_encode($request_body), 0, 4000),
            'status'        => $status,
            'response_body' => is_string($response_body) ? mb_substr($response_body, 0, 4000) : '',
            'duration_ms'   => round($duration_ms, 1),
        ]);
        $log = array_slice($log, 0, self::LOG_MAX_ENTRIES);
        set_transient(self::LOG_TRANSIENT, $log, HOUR_IN_SECONDS);
    }

    public static function build_oauth_start_url(array $settings): string {
        $base = 'https://api.insynia.ai/api/integrations/github/oauth/start';
        $project_id = (string) ($settings['project_id'] ?? ($settings['workspace_id'] ?? ''));
        $args = [
            'siteId'    => $settings['site_id'],
            'projectId' => $project_id,
            'returnUrl' => admin_url('admin.php?page=wpgp'),
        ];

        return add_query_arg($args, $base);
    }

    public static function get_connection(array $settings) {
        if (empty($settings['site_id'])) {
            return new WP_Error('wpgp_missing_site_id', __('Site ID is required.', 'wp-github-push'));
        }

        return self::request(
            'GET',
            '/integrations/github/connections/' . rawurlencode($settings['site_id']),
            [
                'projectId' => (string) ($settings['project_id'] ?? ($settings['workspace_id'] ?? '')),
            ],
            $settings
        );
    }

    public static function connect_with_personal_access_token(array $settings, string $personal_access_token) {
        if (empty($settings['site_id'])) {
            return new WP_Error('wpgp_missing_site_id', __('Site ID is required.', 'wp-github-push'));
        }
        if (empty($settings['hmac_secret'])) {
            return new WP_Error('wpgp_missing_hmac_secret', __('HMAC Secret is required.', 'wp-github-push'));
        }

        $payload = [
            'siteId' => (string) $settings['site_id'],
            'projectId' => (string) ($settings['project_id'] ?? ($settings['workspace_id'] ?? '')),
            'personalAccessToken' => $personal_access_token,
        ];

        return self::request('POST', '/integrations/github/pat/connect', $payload, $settings);
    }

    public static function register_site(array $settings, string $access_token) {
        $project_id = (string) ($settings['project_id'] ?? ($settings['workspace_id'] ?? ''));
        if ('' === $project_id) {
            return new WP_Error('wpgp_missing_project_id', __('Project ID is required.', 'wp-github-push'));
        }
        if ('' === trim($access_token)) {
            return new WP_Error('wpgp_missing_access_token', __('Insynia access token is required.', 'wp-github-push'));
        }

        $base = untrailingslashit((string) ($settings['backend_base_url'] ?? ''));
        if ('' === $base) {
            return new WP_Error('wpgp_missing_backend_url', __('Backend base URL is required.', 'wp-github-push'));
        }

        $body = wp_json_encode([
            'projectId' => $project_id,
        ]);
        $register_url = $base . '/sites/register';
        $start = microtime(true);
        $response = wp_remote_request(
            $register_url,
            [
                'method'  => 'POST',
                'timeout' => 60,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . trim($access_token),
                ],
                'body' => $body,
            ]
        );
        $duration_ms = (microtime(true) - $start) * 1000;

        if (is_wp_error($response)) {
            self::log_api_call('POST', $register_url, $body, 0, 'WP_Error: ' . $response->get_error_message(), $duration_ms);
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        self::log_api_call('POST', $register_url, $body, $status, $response_body, $duration_ms);
        $decoded = json_decode((string) $response_body, true);

        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded) && isset($decoded['error']['message'])
                ? (string) $decoded['error']['message']
                : __('Backend request failed.', 'wp-github-push');
            return new WP_Error('wpgp_backend_error', $message, ['status' => $status, 'body' => $decoded]);
        }

        return is_array($decoded) ? $decoded : [];
    }

    public static function save_repo_selection(array $settings) {
        $payload = [
            'siteId'       => $settings['site_id'],
            'projectId'    => (string) ($settings['project_id'] ?? ($settings['workspace_id'] ?? '')),
            'connectionId' => $settings['connection_id'],
            'repo'         => $settings['repo'],
            'branch'       => $settings['branch'],
        ];

        return self::request('POST', '/integrations/github/selection', $payload, $settings);
    }

    public static function disconnect_connection(array $settings) {
        $payload = [
            'siteId' => $settings['site_id'],
            'connectionId' => $settings['connection_id'],
        ];

        return self::request('POST', '/integrations/github/disconnect', $payload, $settings);
    }

    public static function create_push_job(array $settings, array $payload) {
        return self::request('POST', '/integrations/github/push', $payload, $settings);
    }

    public static function get_push_job(array $settings, string $job_id) {
        return self::request(
            'GET',
            '/integrations/github/push/' . rawurlencode($job_id),
            [
                'siteId'       => $settings['site_id'],
                'projectId'    => (string) ($settings['project_id'] ?? ($settings['workspace_id'] ?? '')),
                'connectionId' => $settings['connection_id'],
                'repo'         => $settings['repo'],
                'branch'       => $settings['branch'],
            ],
            $settings
        );
    }

    public static function create_pull_job(array $settings, array $payload) {
        return self::request('POST', '/integrations/github/pull', $payload, $settings);
    }

    public static function get_pull_job(array $settings, string $job_id) {
        return self::request(
            'GET',
            '/integrations/github/pull/' . rawurlencode($job_id),
            ['siteId' => $settings['site_id']],
            $settings
        );
    }

    private static function request(string $method, string $path, array $data, array $settings) {
        $base = untrailingslashit((string) ($settings['backend_base_url'] ?? ''));
        if ('' === $base) {
            return new WP_Error('wpgp_missing_backend_url', __('Backend base URL is required.', 'wp-github-push'));
        }

        $url = $base . $path;
        $args = [
            'method'  => strtoupper($method),
            'timeout' => 60,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        if ('GET' === strtoupper($method) && !empty($data)) {
            $url = add_query_arg($data, $url);
            self::attach_signature_headers($args['headers'], '', $settings);
        } elseif ('GET' !== strtoupper($method)) {
            $body = wp_json_encode($data);
            $args['body'] = $body;
            $args['headers']['Content-Type'] = 'application/json';
            self::attach_signature_headers($args['headers'], $body, $settings);
        } else {
            self::attach_signature_headers($args['headers'], '', $settings);
        }

        $start = microtime(true);
        $response = wp_remote_request($url, $args);
        $duration_ms = (microtime(true) - $start) * 1000;

        if (is_wp_error($response)) {
            self::log_api_call($args['method'], $url, $args['body'] ?? '', 0, 'WP_Error: ' . $response->get_error_message(), $duration_ms);
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        self::log_api_call($args['method'], $url, $args['body'] ?? '', $status, $body, $duration_ms);
        $decoded = json_decode((string) $body, true);

        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded) && isset($decoded['error']['message'])
                ? (string) $decoded['error']['message']
                : __('Backend request failed.', 'wp-github-push');

            return new WP_Error('wpgp_backend_error', $message, ['status' => $status, 'body' => $decoded]);
        }

        return is_array($decoded) ? $decoded : [];
    }

    private static function attach_signature_headers(array &$headers, string $body, array $settings): void {
        $site_id = (string) ($settings['site_id'] ?? '');
        $secret = (string) ($settings['hmac_secret'] ?? '');
        $timestamp = time();

        if ('' === $site_id || '' === $secret) {
            return;
        }

        $headers['X-WPGP-Site-Id'] = $site_id;
        $headers['X-WPGP-Timestamp'] = (string) $timestamp;
        $headers['X-WPGP-Signature'] = 'sha256=' . WPGP_Security::sign_payload($body, $secret, $timestamp);
    }
}

