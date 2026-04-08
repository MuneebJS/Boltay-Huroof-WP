<?php

defined('ABSPATH') || exit;

final class WPGP_Push_Service {
    private const ALLOWED_PULL_PREFIXES = ['themes/', 'plugins/', 'mu-plugins/'];

    public static function init(): void {
        add_action('admin_post_wpgp_push', [self::class, 'handle_push']);
        add_action('admin_post_wpgp_pull', [self::class, 'handle_pull']);
        add_action('admin_post_wpgp_disconnect', [self::class, 'handle_disconnect']);
        add_action('admin_post_wpgp_connect_pat', [self::class, 'handle_connect_pat']);
        add_action('admin_post_wpgp_sync_selection', [self::class, 'handle_sync_selection']);
        add_action('wp_ajax_wpgp_direct_push', [self::class, 'ajax_direct_push']);
        add_action('wp_ajax_wpgp_direct_pull', [self::class, 'ajax_direct_pull']);
    }

    // ------------------------------------------------------------------
    // Push – reads local files, pushes to GitHub in one atomic commit
    // ------------------------------------------------------------------

    public static function handle_push(): void {
        WPGP_Security::ensure_admin();
        WPGP_Security::verify_nonce('wpgp_push_nonce', 'wpgp_push_action');

        $settings = WPGP_Settings::get();
        $error = self::validate_github_settings($settings);
        if (is_wp_error($error)) {
            self::redirect_with_notice('error', $error->get_error_message());
        }

        @set_time_limit(300);

        $scan = WPGP_File_Scanner::build_manifest($settings);
        if (!empty($scan['error']) && is_wp_error($scan['error'])) {
            self::redirect_with_notice('error', $scan['error']->get_error_message());
        }

        $manifest = $scan['manifest'] ?? [];
        if (empty($manifest)) {
            self::redirect_with_notice('error', __('No files were eligible for push.', 'wp-github-push'));
        }

        $files = [];
        foreach ($manifest as $entry) {
            $files[] = [
                'path'           => $entry['relativePath'],
                'content_base64' => $entry['contentBase64'],
            ];
        }

        $commit_message = sanitize_text_field($_POST['wpgp_commit_message'] ?? '');
        if ('' === $commit_message) {
            $commit_message = 'Sync from WordPress';
        }

        $result = WPGP_GitHub_API::push_files(
            $settings['github_pat'],
            $settings['repo'],
            $settings['branch'],
            $commit_message,
            $files
        );

        if (is_wp_error($result)) {
            self::redirect_with_notice('error', __('Push failed: ', 'wp-github-push') . $result->get_error_message());
        }

        WPGP_Settings::update(['last_push_at' => gmdate('c')]);

        self::redirect_with_notice(
            'success',
            sprintf(
                __('Push successful – %d files committed (%s).', 'wp-github-push'),
                $result['files_pushed'],
                substr($result['commit_sha'], 0, 7)
            )
        );
    }

    /**
     * AJAX variant for push (used by the JS progress UI).
     */
    public static function ajax_direct_push(): void {
        WPGP_Security::ensure_admin();
        check_ajax_referer('wpgp_push_action', 'nonce');

        $settings = WPGP_Settings::get();
        $error = self::validate_github_settings($settings);
        if (is_wp_error($error)) {
            wp_send_json_error(['message' => $error->get_error_message()], 400);
        }

        @set_time_limit(300);

        $scan = WPGP_File_Scanner::build_manifest($settings);
        if (!empty($scan['error']) && is_wp_error($scan['error'])) {
            wp_send_json_error(['message' => $scan['error']->get_error_message()], 400);
        }

        $manifest = $scan['manifest'] ?? [];
        if (empty($manifest)) {
            wp_send_json_error(['message' => __('No files were eligible for push.', 'wp-github-push')], 400);
        }

        $files = [];
        foreach ($manifest as $entry) {
            $files[] = [
                'path'           => $entry['relativePath'],
                'content_base64' => $entry['contentBase64'],
            ];
        }

        $commit_message = sanitize_text_field($_POST['commit_message'] ?? 'Sync from WordPress');

        $result = WPGP_GitHub_API::push_files(
            $settings['github_pat'],
            $settings['repo'],
            $settings['branch'],
            $commit_message,
            $files
        );

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 500);
        }

        WPGP_Settings::update(['last_push_at' => gmdate('c')]);

        wp_send_json_success([
            'commit_sha'    => $result['commit_sha'],
            'files_pushed'  => $result['files_pushed'],
            'text_inline'   => $result['text_inline'] ?? 0,
            'blobs_created' => $result['blobs_created'] ?? 0,
        ]);
    }

    // ------------------------------------------------------------------
    // Pull – fetches repo tree from GitHub, writes matching files locally
    // ------------------------------------------------------------------

    public static function handle_pull(): void {
        WPGP_Security::ensure_admin();
        WPGP_Security::verify_nonce('wpgp_pull_nonce', 'wpgp_pull_action');

        $settings = WPGP_Settings::get();
        $error = self::validate_github_settings($settings);
        if (is_wp_error($error)) {
            self::redirect_with_notice('error', $error->get_error_message());
        }

        @set_time_limit(300);

        $tree_result = WPGP_GitHub_API::get_tree(
            $settings['github_pat'],
            $settings['repo'],
            $settings['branch']
        );
        if (is_wp_error($tree_result)) {
            self::redirect_with_notice('error', __('Pull failed: ', 'wp-github-push') . $tree_result->get_error_message());
        }

        $tree_items = $tree_result['tree'] ?? [];
        $eligible   = self::filter_pull_tree($tree_items);

        if (empty($eligible)) {
            self::redirect_with_notice('error', __('No eligible files found in the repository.', 'wp-github-push'));
        }

        $report = self::download_and_apply($settings, $eligible);

        WPGP_Settings::update(['last_pull_at' => gmdate('c')]);

        if (!empty($report['errors'])) {
            self::redirect_with_notice(
                'error',
                sprintf(
                    __('Pull completed with errors. Changed: %d, Errors: %d.', 'wp-github-push'),
                    $report['changed'],
                    count($report['errors'])
                )
            );
        }

        self::redirect_with_notice(
            'success',
            sprintf(
                __('Pull successful – %d files updated.', 'wp-github-push'),
                $report['changed']
            )
        );
    }

    /**
     * AJAX variant for pull.
     */
    public static function ajax_direct_pull(): void {
        WPGP_Security::ensure_admin();
        check_ajax_referer('wpgp_pull_action', 'nonce');

        $settings = WPGP_Settings::get();
        $error = self::validate_github_settings($settings);
        if (is_wp_error($error)) {
            wp_send_json_error(['message' => $error->get_error_message()], 400);
        }

        @set_time_limit(300);

        $tree_result = WPGP_GitHub_API::get_tree(
            $settings['github_pat'],
            $settings['repo'],
            $settings['branch']
        );
        if (is_wp_error($tree_result)) {
            wp_send_json_error(['message' => $tree_result->get_error_message()], 500);
        }

        $eligible = self::filter_pull_tree($tree_result['tree'] ?? []);
        if (empty($eligible)) {
            wp_send_json_error(['message' => __('No eligible files found in the repository.', 'wp-github-push')], 400);
        }

        $report = self::download_and_apply($settings, $eligible);

        WPGP_Settings::update(['last_pull_at' => gmdate('c')]);

        if (!empty($report['errors'])) {
            wp_send_json_error(['report' => $report], 500);
        }

        wp_send_json_success($report);
    }

    // ------------------------------------------------------------------
    // PAT connection – validate via GitHub API and store locally
    // ------------------------------------------------------------------

    public static function handle_connect_pat(): void {
        WPGP_Security::ensure_admin();
        WPGP_Security::verify_nonce('wpgp_connect_pat_nonce', 'wpgp_connect_pat_action');

        $pat = sanitize_text_field((string) ($_POST['wpgp_personal_access_token'] ?? ''));
        if ('' === $pat) {
            self::redirect_with_notice('error', __('Personal access token is required.', 'wp-github-push'));
        }

        $user = WPGP_GitHub_API::validate_token($pat);
        if (is_wp_error($user)) {
            self::redirect_with_notice('error', __('Invalid token: ', 'wp-github-push') . $user->get_error_message());
        }

        $username = sanitize_text_field((string) ($user['login'] ?? ''));
        if ('' === $username) {
            self::redirect_with_notice('error', __('Could not retrieve GitHub username from token.', 'wp-github-push'));
        }

        WPGP_Settings::update([
            'github_pat'      => $pat,
            'github_username' => $username,
        ]);

        self::redirect_with_notice(
            'success',
            sprintf(__('GitHub connected as %s.', 'wp-github-push'), $username)
        );
    }

    // ------------------------------------------------------------------
    // Disconnect – clear stored PAT
    // ------------------------------------------------------------------

    public static function handle_disconnect(): void {
        WPGP_Security::ensure_admin();
        WPGP_Security::verify_nonce('wpgp_disconnect_nonce', 'wpgp_disconnect_action');

        WPGP_Settings::update([
            'github_pat'      => '',
            'github_username' => '',
            'connection_id'   => '',
        ]);

        self::redirect_with_notice('success', __('GitHub connection disconnected.', 'wp-github-push'));
    }

    // ------------------------------------------------------------------
    // Repo/branch selection (kept simple – just save to settings)
    // ------------------------------------------------------------------

    public static function handle_sync_selection(): void {
        WPGP_Security::ensure_admin();
        WPGP_Security::verify_nonce('wpgp_sync_selection_nonce', 'wpgp_sync_selection_action');

        $repo   = sanitize_text_field((string) ($_POST['wpgp_repo'] ?? ''));
        $branch = sanitize_text_field((string) ($_POST['wpgp_branch'] ?? ''));

        WPGP_Settings::update([
            'repo'   => $repo,
            'branch' => $branch,
        ]);

        self::redirect_with_notice('success', __('Repository and branch saved.', 'wp-github-push'));
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private static function validate_github_settings(array $settings) {
        if ('' === (string) ($settings['github_pat'] ?? '')) {
            return new WP_Error('wpgp_missing_pat', __('GitHub PAT is required. Connect via PAT first.', 'wp-github-push'));
        }
        if ('' === (string) ($settings['repo'] ?? '')) {
            return new WP_Error('wpgp_missing_repo', __('Repository is required.', 'wp-github-push'));
        }
        if ('' === (string) ($settings['branch'] ?? '')) {
            return new WP_Error('wpgp_missing_branch', __('Branch is required.', 'wp-github-push'));
        }
        return null;
    }

    /**
     * Filter the GitHub tree to only include blobs under allowed prefixes.
     */
    private static function filter_pull_tree(array $tree_items): array {
        $eligible = [];
        foreach ($tree_items as $item) {
            if ('blob' !== ($item['type'] ?? '')) {
                continue;
            }
            $path = (string) ($item['path'] ?? '');
            if (!self::is_allowed_pull_path($path)) {
                continue;
            }
            $eligible[] = $item;
        }
        return $eligible;
    }

    /**
     * Download each blob and write it to the wp-content directory.
     */
    private static function download_and_apply(array $settings, array $eligible): array {
        $backup_root = self::prepare_backup_root();
        $report = [
            'changed' => 0,
            'skipped' => 0,
            'errors'  => [],
        ];

        foreach ($eligible as $item) {
            $relative_path = self::sanitize_relative_path((string) ($item['path'] ?? ''));
            if ('' === $relative_path) {
                $report['skipped']++;
                continue;
            }

            $content = WPGP_GitHub_API::get_blob_content(
                $settings['github_pat'],
                $settings['repo'],
                (string) $item['sha']
            );

            if (is_wp_error($content)) {
                $report['errors'][] = sprintf('%s: %s', $relative_path, $content->get_error_message());
                continue;
            }

            $target_path = trailingslashit(WP_CONTENT_DIR) . $relative_path;
            self::backup_file_if_exists($target_path, $backup_root);

            $parent_dir = dirname($target_path);
            if (!is_dir($parent_dir) && !wp_mkdir_p($parent_dir)) {
                $report['errors'][] = sprintf(__('Failed to create directory for %s', 'wp-github-push'), $relative_path);
                continue;
            }

            if (false === file_put_contents($target_path, $content)) {
                $report['errors'][] = sprintf(__('Failed to write %s', 'wp-github-push'), $relative_path);
                continue;
            }

            $report['changed']++;
        }

        return $report;
    }

    private static function sanitize_relative_path(string $path): string {
        $normalized = wp_normalize_path($path);
        $normalized = ltrim($normalized, '/');
        if ('' === $normalized || false !== strpos($normalized, '..') || false !== strpos($normalized, "\0")) {
            return '';
        }
        return $normalized;
    }

    private static function is_allowed_pull_path(string $relative_path): bool {
        foreach (self::ALLOWED_PULL_PREFIXES as $prefix) {
            if (0 === strpos($relative_path, $prefix)) {
                return true;
            }
        }
        return false;
    }

    private static function prepare_backup_root(): string {
        $upload_dir = wp_upload_dir();
        $base_dir = trailingslashit($upload_dir['basedir']) . 'wpgp-backups/' . gmdate('Ymd-His');
        wp_mkdir_p($base_dir);
        return $base_dir;
    }

    private static function backup_file_if_exists(string $target_path, string $backup_root): void {
        if (!file_exists($target_path) || !is_file($target_path)) {
            return;
        }

        $relative = ltrim(str_replace(wp_normalize_path(WP_CONTENT_DIR), '', wp_normalize_path($target_path)), '/');
        $backup_path = trailingslashit($backup_root) . $relative;
        $backup_dir = dirname($backup_path);
        if (!is_dir($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        @copy($target_path, $backup_path);
    }

    private static function redirect_with_notice(string $type, string $message): void {
        $url = add_query_arg(
            [
                'page' => 'wpgp',
                'wpgp_notice_type' => rawurlencode($type),
                'wpgp_notice_message' => rawurlencode($message),
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($url);
        exit;
    }
}
