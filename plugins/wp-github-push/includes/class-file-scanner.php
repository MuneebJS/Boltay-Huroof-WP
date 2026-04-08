<?php

defined('ABSPATH') || exit;

final class WPGP_File_Scanner {
    private const MAX_FILE_SIZE_BYTES = 5242880;  // 5 MB per file
    private const MAX_PAYLOAD_BYTES   = 52428800; // 50 MB total

    private const ALLOWED_EXTENSIONS = [
        'php', 'css', 'js', 'jsx', 'ts', 'tsx',
        'html', 'htm', 'json', 'xml',
        'twig', 'mustache', 'phtml', 'tpl',
        'yml', 'yaml', 'sql',
        'pot', 'po', 'htaccess', 'env',
        'lock', 'conf', 'txt', 'md',
    ];

    private const SKIP_DIR_SEGMENTS = [
        'node_modules',
        'vendor',
        '.git',
        '.svn',
        'dist',
        'build',
        '.cache',
        '.tmp',
    ];

    private const SKIP_FILENAMES = [
        '.DS_Store',
        'Thumbs.db',
        'desktop.ini',
    ];

    /**
     * Build a manifest of only the essential, user-editable files from the
     * active theme (+parent), active plugins, and mu-plugins.
     */
    public static function build_manifest(array $settings): array {
        $roots          = self::resolve_scan_roots();
        $user_patterns  = self::parse_user_patterns((string) ($settings['exclude_patterns'] ?? ''));
        $manifest       = [];
        $total_size     = 0;

        foreach ($roots as $root) {
            // Single-file plugin (e.g. plugins/hello.php)
            if (is_file($root)) {
                $file_info = new SplFileInfo($root);
                $result    = self::process_file($file_info, $user_patterns, $total_size);
                if (null === $result) {
                    continue;
                }
                if (is_wp_error($result)) {
                    return ['error' => $result, 'manifest' => []];
                }
                $total_size += $result['size'];
                $manifest[]  = $result;
                continue;
            }

            if (!is_dir($root)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file_info) {
                if (!$file_info instanceof SplFileInfo || !$file_info->isFile()) {
                    continue;
                }

                $result = self::process_file($file_info, $user_patterns, $total_size);
                if (null === $result) {
                    continue;
                }
                if (is_wp_error($result)) {
                    return ['error' => $result, 'manifest' => []];
                }
                $total_size += $result['size'];
                $manifest[]  = $result;
            }
        }

        usort($manifest, static function (array $a, array $b): int {
            return strcmp((string) ($a['relativePath'] ?? ''), (string) ($b['relativePath'] ?? ''));
        });

        return ['manifest' => $manifest, 'error' => null];
    }

    // ------------------------------------------------------------------
    // Resolve which directories to scan
    // ------------------------------------------------------------------

    private static function resolve_scan_roots(): array {
        $roots       = [];
        $content_dir = trailingslashit(WP_CONTENT_DIR);

        // Active theme (child) + parent theme if different
        $active_theme = get_stylesheet();
        $parent_theme = get_template();

        $roots[] = $content_dir . 'themes/' . $active_theme;
        if ($parent_theme !== $active_theme) {
            $roots[] = $content_dir . 'themes/' . $parent_theme;
        }

        // Active plugins only
        $active_plugins = (array) get_option('active_plugins', []);
        $seen_dirs      = [];

        foreach ($active_plugins as $plugin_path) {
            $plugin_path = (string) $plugin_path;
            if (false !== strpos($plugin_path, '/')) {
                $dir = explode('/', $plugin_path, 2)[0];
                if ('' !== $dir && !isset($seen_dirs[$dir])) {
                    $seen_dirs[$dir] = true;
                    $roots[]         = $content_dir . 'plugins/' . $dir;
                }
            } else {
                // Single-file plugin (e.g. hello.php) — handled as individual file below
                $single = $content_dir . 'plugins/' . $plugin_path;
                if (is_file($single)) {
                    $roots[] = $single;
                }
            }
        }

        // mu-plugins (always active by definition, usually very few files)
        $mu_dir = $content_dir . 'mu-plugins';
        if (is_dir($mu_dir)) {
            $roots[] = $mu_dir;
        }

        return $roots;
    }

    // ------------------------------------------------------------------
    // Filtering logic
    // ------------------------------------------------------------------

    private static function should_skip(string $relative_path, SplFileInfo $file_info, array $user_patterns): bool {
        $basename = $file_info->getBasename();

        // Skip known junk filenames
        if (in_array($basename, self::SKIP_FILENAMES, true)) {
            return true;
        }

        // Extension allowlist — reject anything not in the list
        $ext = strtolower($file_info->getExtension());
        if ('' !== $ext && !in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return true;
        }
        // Files with no extension that aren't named specifically (e.g. LICENSE, Makefile) — skip
        if ('' === $ext) {
            return true;
        }

        // Skip minified / compiled files by name
        if (self::is_minified($basename)) {
            return true;
        }

        // Skip files inside blacklisted directory segments
        if (self::in_skipped_directory($relative_path)) {
            return true;
        }

        // User-configured exclude patterns
        foreach ($user_patterns as $pattern) {
            if (fnmatch($pattern, $relative_path)) {
                return true;
            }
        }

        return false;
    }

    private static function is_minified(string $basename): bool {
        $lower   = strtolower($basename);
        $suffixes = ['.min.js', '.min.css', '.bundle.js', '.bundle.css', '.min.map', '.js.map', '.css.map'];
        foreach ($suffixes as $suffix) {
            if (substr($lower, -strlen($suffix)) === $suffix) {
                return true;
            }
        }
        return false;
    }

    private static function in_skipped_directory(string $relative_path): bool {
        $segments = explode('/', $relative_path);
        array_pop($segments); // remove filename
        foreach ($segments as $segment) {
            if (in_array($segment, self::SKIP_DIR_SEGMENTS, true)) {
                return true;
            }
        }
        return false;
    }

    // ------------------------------------------------------------------
    // File processing
    // ------------------------------------------------------------------

    /**
     * Process a single file: filter it, read content, return manifest entry.
     *
     * @return array|WP_Error|null  Manifest entry, WP_Error if payload limit hit, null to skip.
     */
    private static function process_file(SplFileInfo $file_info, array $user_patterns, int $running_total) {
        $absolute_path = $file_info->getPathname();
        $relative_path = self::to_relative_path($absolute_path);

        if (self::should_skip($relative_path, $file_info, $user_patterns)) {
            return null;
        }

        $size = (int) $file_info->getSize();
        if ($size <= 0 || $size > self::MAX_FILE_SIZE_BYTES) {
            return null;
        }

        $content = file_get_contents($absolute_path);
        if (false === $content) {
            return null;
        }

        if (($running_total + $size) > self::MAX_PAYLOAD_BYTES) {
            return new WP_Error(
                'wpgp_payload_too_large',
                __('Total payload exceeded maximum allowed size.', 'wp-github-push')
            );
        }

        return [
            'relativePath'  => $relative_path,
            'sha256'        => hash('sha256', $content),
            'size'          => $size,
            'modifiedAt'    => gmdate('c', (int) $file_info->getMTime()),
            'contentBase64' => base64_encode($content),
        ];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private static function to_relative_path(string $absolute_path): string {
        $wp_content = trailingslashit(WP_CONTENT_DIR);
        $relative   = str_replace($wp_content, '', wp_normalize_path($absolute_path));
        return ltrim($relative, '/');
    }

    private static function parse_user_patterns(string $raw): array {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $lines = array_map('trim', $lines);
        return array_values(array_filter($lines, static fn ($line) => '' !== $line));
    }
}
