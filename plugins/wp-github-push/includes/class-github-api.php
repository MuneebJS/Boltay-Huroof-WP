<?php

defined('ABSPATH') || exit;

/**
 * Direct GitHub API client using the Git Data API for atomic commits
 * and the Trees/Blobs API for pulling repository content.
 */
final class WPGP_GitHub_API {
    private const API_BASE = 'https://api.github.com';
    private const LOG_TRANSIENT = 'wpgp_api_debug_log';
    private const LOG_MAX_ENTRIES = 100;

    private const TEXT_EXTENSIONS = [
        'php', 'css', 'js', 'jsx', 'ts', 'tsx', 'html', 'htm', 'txt', 'json',
        'xml', 'md', 'svg', 'yml', 'yaml', 'ini', 'pot', 'po', 'sql', 'twig',
        'mustache', 'map', 'less', 'scss', 'sass', 'csv', 'htaccess', 'lock',
        'conf', 'cfg', 'sh', 'bat', 'vue', 'svelte', 'graphql', 'gql', 'env',
        'example', 'dist', 'tpl', 'phtml',
    ];

    /**
     * Validate a PAT and return the authenticated GitHub user info.
     */
    public static function validate_token(string $pat) {
        return self::request('GET', '/user', $pat);
    }

    /**
     * Push multiple files in a single atomic commit via the Git Data API.
     *
     * Uses inline content in the tree endpoint for text files so that GitHub
     * creates the blobs server-side. This reduces the total API calls from
     * N+4 (one per file) down to ~5 regardless of file count.
     *
     * Binary files still require a separate blob creation call.
     *
     * @param string $pat            GitHub Personal Access Token.
     * @param string $repo           "owner/repo" format.
     * @param string $branch         Target branch name.
     * @param string $commit_message Commit message.
     * @param array  $files          Each entry: ['path' => string, 'content_base64' => string].
     * @return array|WP_Error
     */
    public static function push_files(string $pat, string $repo, string $branch, string $commit_message, array $files) {
        // 1. Resolve branch HEAD
        $ref = self::request('GET', '/repos/' . $repo . '/git/ref/heads/' . rawurlencode($branch), $pat);
        if (is_wp_error($ref)) {
            return $ref;
        }
        $head_sha = $ref['object']['sha'] ?? '';
        if ('' === $head_sha) {
            return new WP_Error('wpgp_gh_no_ref', __('Could not resolve branch HEAD.', 'wp-github-push'));
        }

        // 2. Get base tree SHA from HEAD commit
        $head_commit = self::request('GET', '/repos/' . $repo . '/git/commits/' . rawurlencode($head_sha), $pat);
        if (is_wp_error($head_commit)) {
            return $head_commit;
        }
        $base_tree_sha = $head_commit['tree']['sha'] ?? '';

        // 3. Build tree entries — inline content for text, blob SHA for binary
        $tree_items  = [];
        $blob_needed = [];

        foreach ($files as $file) {
            $raw = base64_decode($file['content_base64'], true);
            if (false === $raw) {
                continue;
            }

            $ext     = strtolower(pathinfo($file['path'], PATHINFO_EXTENSION));
            $is_text = (in_array($ext, self::TEXT_EXTENSIONS, true) || '' === $ext)
                       && mb_check_encoding($raw, 'UTF-8');

            if ($is_text) {
                $tree_items[] = [
                    'path'    => $file['path'],
                    'mode'    => '100644',
                    'type'    => 'blob',
                    'content' => $raw,
                ];
            } else {
                $blob_needed[] = $file;
            }
        }

        // Create blobs only for binary files (typically images, fonts, etc.)
        foreach ($blob_needed as $file) {
            $blob = self::request('POST', '/repos/' . $repo . '/git/blobs', $pat, [
                'content'  => $file['content_base64'],
                'encoding' => 'base64',
            ]);
            if (is_wp_error($blob)) {
                return $blob;
            }

            $tree_items[] = [
                'path' => $file['path'],
                'mode' => '100644',
                'type' => 'blob',
                'sha'  => $blob['sha'],
            ];
        }

        if (empty($tree_items)) {
            return new WP_Error('wpgp_gh_no_files', __('No files to push.', 'wp-github-push'));
        }

        // 4. Create tree (single request carries all file content)
        $new_tree = self::request('POST', '/repos/' . $repo . '/git/trees', $pat, [
            'base_tree' => $base_tree_sha,
            'tree'      => $tree_items,
        ], 120);
        if (is_wp_error($new_tree)) {
            return $new_tree;
        }

        // 5. Create commit
        $new_commit = self::request('POST', '/repos/' . $repo . '/git/commits', $pat, [
            'message' => $commit_message,
            'tree'    => $new_tree['sha'],
            'parents' => [$head_sha],
        ]);
        if (is_wp_error($new_commit)) {
            return $new_commit;
        }

        // 6. Update branch ref
        $update = self::request('PATCH', '/repos/' . $repo . '/git/refs/heads/' . rawurlencode($branch), $pat, [
            'sha'   => $new_commit['sha'],
            'force' => false,
        ]);
        if (is_wp_error($update)) {
            return $update;
        }

        return [
            'commit_sha'   => $new_commit['sha'],
            'files_pushed' => count($tree_items),
            'text_inline'  => count($tree_items) - count($blob_needed),
            'blobs_created' => count($blob_needed),
        ];
    }

    /**
     * Get the full recursive tree for a branch.
     *
     * @return array|WP_Error  ['commit_sha' => ..., 'tree' => [...items...], 'truncated' => bool]
     */
    public static function get_tree(string $pat, string $repo, string $branch) {
        $ref = self::request('GET', '/repos/' . $repo . '/git/ref/heads/' . rawurlencode($branch), $pat);
        if (is_wp_error($ref)) {
            return $ref;
        }
        $head_sha = $ref['object']['sha'] ?? '';
        if ('' === $head_sha) {
            return new WP_Error('wpgp_gh_no_ref', __('Could not resolve branch HEAD.', 'wp-github-push'));
        }

        $commit = self::request('GET', '/repos/' . $repo . '/git/commits/' . rawurlencode($head_sha), $pat);
        if (is_wp_error($commit)) {
            return $commit;
        }
        $tree_sha = $commit['tree']['sha'] ?? '';

        $tree = self::request('GET', '/repos/' . $repo . '/git/trees/' . rawurlencode($tree_sha) . '?recursive=1', $pat);
        if (is_wp_error($tree)) {
            return $tree;
        }

        return [
            'commit_sha' => $head_sha,
            'tree'       => $tree['tree'] ?? [],
            'truncated'  => !empty($tree['truncated']),
        ];
    }

    /**
     * Download a blob's content by its SHA and return the raw decoded bytes.
     */
    public static function get_blob_content(string $pat, string $repo, string $sha) {
        $blob = self::request('GET', '/repos/' . $repo . '/git/blobs/' . rawurlencode($sha), $pat);
        if (is_wp_error($blob)) {
            return $blob;
        }

        $encoding = $blob['encoding'] ?? 'base64';
        $content  = $blob['content'] ?? '';

        if ('base64' === $encoding) {
            $clean   = str_replace(["\n", "\r", ' '], '', $content);
            $decoded = base64_decode($clean, true);
            if (false === $decoded) {
                return new WP_Error('wpgp_gh_decode', __('Failed to decode blob content.', 'wp-github-push'));
            }
            return $decoded;
        }

        return $content;
    }

    /**
     * PUT a single file via the Contents API (creates one commit per file).
     * Kept for simple single-file operations.
     */
    public static function put_file(string $pat, string $repo, string $path, string $raw_content, string $message, string $branch, string $existing_sha = '') {
        $body = [
            'message' => $message,
            'content' => base64_encode($raw_content),
            'branch'  => $branch,
        ];
        if ('' !== $existing_sha) {
            $body['sha'] = $existing_sha;
        }

        return self::request('PUT', '/repos/' . $repo . '/contents/' . ltrim($path, '/'), $pat, $body);
    }

    // ------------------------------------------------------------------
    // HTTP transport
    // ------------------------------------------------------------------

    /**
     * @param int $timeout HTTP timeout in seconds (default 30, use higher for large payloads).
     */
    private static function request(string $method, string $endpoint, string $pat, ?array $body = null, int $timeout = 30) {
        $url = self::API_BASE . $endpoint;

        $args = [
            'method'  => strtoupper($method),
            'timeout' => $timeout,
            'headers' => [
                'Accept'               => 'application/vnd.github+json',
                'Authorization'        => 'Bearer ' . $pat,
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent'           => 'WP-GitHub-Push/' . WPGP_VERSION,
            ],
        ];

        $request_body_raw = '';
        if (null !== $body) {
            $request_body_raw = (string) wp_json_encode($body);
            $args['body']                    = $request_body_raw;
            $args['headers']['Content-Type'] = 'application/json';
        }

        $start    = microtime(true);
        $response = wp_remote_request($url, $args);
        $elapsed  = (microtime(true) - $start) * 1000;

        if (is_wp_error($response)) {
            self::log($method, $url, $request_body_raw, 0, 'WP_Error: ' . $response->get_error_message(), $elapsed);
            return $response;
        }

        $status        = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        self::log($method, $url, $request_body_raw, $status, $response_body, $elapsed);

        $decoded = json_decode($response_body, true);

        if ($status < 200 || $status >= 300) {
            $msg = is_array($decoded) && !empty($decoded['message'])
                ? (string) $decoded['message']
                : sprintf(__('GitHub API error (HTTP %d).', 'wp-github-push'), $status);

            return new WP_Error('wpgp_github_api_error', $msg, ['status' => $status, 'body' => $decoded]);
        }

        return is_array($decoded) ? $decoded : [];
    }

    // ------------------------------------------------------------------
    // Debug log (shared transient with WPGP_API_Client)
    // ------------------------------------------------------------------

    private static function log(string $method, string $url, string $request_body, int $status, string $response_body, float $duration_ms): void {
        $log = get_transient(self::LOG_TRANSIENT) ?: [];

        $truncate_body = static function (string $raw): string {
            if (strlen($raw) > 4000) {
                return mb_substr($raw, 0, 2000) . "\n…[truncated]…\n" . mb_substr($raw, -500);
            }
            return $raw;
        };

        array_unshift($log, [
            'time'          => gmdate('Y-m-d H:i:s') . ' UTC',
            'method'        => $method,
            'url'           => $url,
            'request_body'  => $truncate_body($request_body),
            'status'        => $status,
            'response_body' => $truncate_body($response_body),
            'duration_ms'   => round($duration_ms, 1),
        ]);

        $log = array_slice($log, 0, self::LOG_MAX_ENTRIES);
        set_transient(self::LOG_TRANSIENT, $log, HOUR_IN_SECONDS);
    }
}
