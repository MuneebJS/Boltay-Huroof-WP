<?php

defined('ABSPATH') || exit;

final class WPGP_File_Scanner {
    private const MAX_FILE_SIZE_BYTES = 5242880; // 5 MB
    private const MAX_PAYLOAD_BYTES = 52428800; // 50 MB

    public static function build_manifest(array $settings): array {
        $roots = [
            trailingslashit(WP_CONTENT_DIR) . 'themes',
            trailingslashit(WP_CONTENT_DIR) . 'plugins',
            trailingslashit(WP_CONTENT_DIR) . 'mu-plugins',
        ];

        $patterns = self::parse_patterns((string) ($settings['exclude_patterns'] ?? ''));
        $manifest = [];
        $total_size = 0;

        foreach ($roots as $root) {
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

                $absolute_path = $file_info->getPathname();
                $relative_path = self::to_relative_path($absolute_path);

                if (self::is_excluded($relative_path, $patterns)) {
                    continue;
                }

                $size = (int) $file_info->getSize();
                if ($size <= 0 || $size > self::MAX_FILE_SIZE_BYTES) {
                    continue;
                }

                $content = file_get_contents($absolute_path);
                if (false === $content) {
                    continue;
                }

                $total_size += $size;
                if ($total_size > self::MAX_PAYLOAD_BYTES) {
                    return [
                        'error' => new WP_Error(
                            'wpgp_payload_too_large',
                            __('Total payload exceeded maximum allowed size.', 'wp-github-push')
                        ),
                        'manifest' => [],
                    ];
                }

                $manifest[] = [
                    'relativePath'  => $relative_path,
                    'sha256'        => hash('sha256', $content),
                    'size'          => $size,
                    'modifiedAt'    => gmdate('c', (int) $file_info->getMTime()),
                    'contentBase64' => base64_encode($content),
                ];
            }
        }

        usort(
            $manifest,
            static function (array $left, array $right): int {
                return strcmp((string) ($left['relativePath'] ?? ''), (string) ($right['relativePath'] ?? ''));
            }
        );

        return ['manifest' => $manifest, 'error' => null];
    }

    private static function to_relative_path(string $absolute_path): string {
        $wp_content = trailingslashit(WP_CONTENT_DIR);
        $relative = str_replace($wp_content, '', wp_normalize_path($absolute_path));
        return ltrim($relative, '/');
    }

    private static function parse_patterns(string $raw): array {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, static fn ($line) => '' !== $line);

        $defaults = [
            'uploads/*',
            '*.log',
            '*.cache',
            '.git/*',
            'node_modules/*',
        ];

        return array_merge($defaults, $lines);
    }

    private static function is_excluded(string $relative_path, array $patterns): bool {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $relative_path) || false !== strpos($relative_path, 'uploads/')) {
                return true;
            }
        }

        return false;
    }
}

