<?php

defined('ABSPATH') || exit;

final class WPGP_Admin_Page {
    public static function init(): void {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('wp_ajax_wpgp_clear_debug_log', [self::class, 'handle_clear_debug_log']);
    }

    public static function handle_clear_debug_log(): void {
        check_ajax_referer('wpgp_clear_debug_log', 'nonce');
        WPGP_API_Client::clear_debug_log();
        wp_send_json_success();
    }

    public static function register_menu(): void {
        add_menu_page(
            __('WP GitHub Push', 'wp-github-push'),
            __('WP GitHub Push', 'wp-github-push'),
            'manage_options',
            'wpgp',
            [self::class, 'render'],
            'dashicons-upload'
        );
    }

    public static function enqueue_assets(string $hook): void {
        if ('toplevel_page_wpgp' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'wpgp-admin',
            WPGP_PLUGIN_URL . 'admin/assets/admin.js',
            ['jquery'],
            WPGP_VERSION,
            true
        );

        wp_localize_script(
            'wpgp-admin',
            'wpgpAdmin',
            [
                'ajaxUrl'   => admin_url('admin-ajax.php'),
                'pushNonce' => wp_create_nonce('wpgp_push_action'),
                'pullNonce' => wp_create_nonce('wpgp_pull_action'),
            ]
        );
    }

    public static function render(): void {
        WPGP_Security::ensure_admin();
        $settings       = WPGP_Settings::get();
        $notice_type    = sanitize_text_field($_GET['wpgp_notice_type'] ?? '');
        $notice_message = sanitize_text_field($_GET['wpgp_notice_message'] ?? '');
        $has_pat        = '' !== (string) ($settings['github_pat'] ?? '');
        $github_user    = (string) ($settings['github_username'] ?? '');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP GitHub Push', 'wp-github-push'); ?></h1>

            <?php self::render_debug_log(); ?>

            <?php if ($notice_type && $notice_message) : ?>
                <div class="notice notice-<?php echo esc_attr('error' === $notice_type ? 'error' : 'success'); ?> is-dismissible">
                    <p><?php echo esc_html($notice_message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Step 1: Connect GitHub via PAT -->
            <h2><?php esc_html_e('Step 1: Connect GitHub', 'wp-github-push'); ?></h2>
            <?php if ($has_pat && '' !== $github_user) : ?>
                <p style="color:green;">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php printf(esc_html__('Connected as %s', 'wp-github-push'), '<strong>' . esc_html($github_user) . '</strong>'); ?>
                </p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                    <input type="hidden" name="action" value="wpgp_disconnect" />
                    <?php wp_nonce_field('wpgp_disconnect_action', 'wpgp_disconnect_nonce'); ?>
                    <?php submit_button(__('Disconnect', 'wp-github-push'), 'secondary', 'submit', false); ?>
                </form>
            <?php else : ?>
                <p><?php esc_html_e('Enter your GitHub Personal Access Token (needs repo scope).', 'wp-github-push'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="wpgp_connect_pat" />
                    <?php wp_nonce_field('wpgp_connect_pat_action', 'wpgp_connect_pat_nonce'); ?>
                    <p>
                        <input
                            type="password"
                            class="regular-text"
                            name="wpgp_personal_access_token"
                            placeholder="<?php esc_attr_e('ghp_xxxxxxxxxxxxxxxxxxxx', 'wp-github-push'); ?>"
                            autocomplete="off"
                            required
                        />
                        <?php submit_button(__('Connect', 'wp-github-push'), 'primary', 'submit', false); ?>
                    </p>
                </form>
            <?php endif; ?>

            <hr />

            <!-- Step 2: Select Repository and Branch -->
            <h2><?php esc_html_e('Step 2: Repository & Branch', 'wp-github-push'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wpgp_sync_selection" />
                <?php wp_nonce_field('wpgp_sync_selection_action', 'wpgp_sync_selection_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wpgp_repo"><?php esc_html_e('Repository', 'wp-github-push'); ?></label></th>
                        <td>
                            <input type="text" class="regular-text" id="wpgp_repo" name="wpgp_repo" value="<?php echo esc_attr($settings['repo']); ?>" placeholder="owner/repo" />
                            <p class="description"><?php esc_html_e('Format: owner/repo (e.g. acme/my-theme)', 'wp-github-push'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wpgp_branch"><?php esc_html_e('Branch', 'wp-github-push'); ?></label></th>
                        <td>
                            <input type="text" class="regular-text" id="wpgp_branch" name="wpgp_branch" value="<?php echo esc_attr($settings['branch']); ?>" placeholder="main" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save', 'wp-github-push')); ?>
            </form>

            <hr />

            <!-- Step 3: Exclude Patterns -->
            <h2><?php esc_html_e('Step 3: Exclude Patterns', 'wp-github-push'); ?></h2>
            <form method="post" action="options.php">
                <?php settings_fields('wpgp_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wpgp_exclude_patterns"><?php esc_html_e('Exclude Patterns (one per line)', 'wp-github-push'); ?></label></th>
                        <td><textarea class="large-text code" rows="6" id="wpgp_exclude_patterns" name="wpgp_settings[exclude_patterns]"><?php echo esc_textarea($settings['exclude_patterns']); ?></textarea></td>
                    </tr>
                </table>
                <?php
                foreach (['backend_base_url', 'site_id', 'project_id', 'connection_id', 'hmac_secret', 'github_pat', 'github_username', 'repo', 'branch', 'last_job_id', 'last_push_at', 'last_pull_job_id', 'last_pull_at'] as $hidden_key) :
                    ?>
                    <input type="hidden" name="wpgp_settings[<?php echo esc_attr($hidden_key); ?>]" value="<?php echo esc_attr($settings[$hidden_key]); ?>" />
                <?php endforeach; ?>
                <?php submit_button(__('Save Settings', 'wp-github-push')); ?>
            </form>

            <hr />

            <!-- Step 4: Push and Pull -->
            <h2><?php esc_html_e('Step 4: Push & Pull', 'wp-github-push'); ?></h2>
            <?php if (!$has_pat) : ?>
                <p class="description"><?php esc_html_e('Connect your GitHub PAT above before pushing or pulling.', 'wp-github-push'); ?></p>
            <?php else : ?>
                <form id="wpgp-push-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="wpgp_push" />
                    <?php wp_nonce_field('wpgp_push_action', 'wpgp_push_nonce'); ?>
                    <p>
                        <label for="wpgp_commit_message"><?php esc_html_e('Commit Message', 'wp-github-push'); ?></label><br />
                        <input type="text" class="regular-text" id="wpgp_commit_message" name="wpgp_commit_message" placeholder="<?php esc_attr_e('Sync from WordPress', 'wp-github-push'); ?>" />
                    </p>
                    <?php submit_button(__('Push to GitHub', 'wp-github-push'), 'primary', 'submit', false, ['id' => 'wpgp-push-btn']); ?>
                    <span id="wpgp-push-spinner" class="spinner" style="float:none;margin-top:0;"></span>
                </form>

                <form id="wpgp-pull-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
                    <input type="hidden" name="action" value="wpgp_pull" />
                    <?php wp_nonce_field('wpgp_pull_action', 'wpgp_pull_nonce'); ?>
                    <?php submit_button(__('Pull from GitHub', 'wp-github-push'), 'secondary', 'submit', false, ['id' => 'wpgp-pull-btn']); ?>
                    <span id="wpgp-pull-spinner" class="spinner" style="float:none;margin-top:0;"></span>
                </form>

                <div id="wpgp-status" style="margin-top:16px;">
                    <p><strong><?php esc_html_e('Last Pushed:', 'wp-github-push'); ?></strong> <?php echo esc_html($settings['last_push_at'] ?: '—'); ?></p>
                    <p><strong><?php esc_html_e('Last Pulled:', 'wp-github-push'); ?></strong> <?php echo esc_html($settings['last_pull_at'] ?: '—'); ?></p>
                    <pre id="wpgp-status-output" style="max-height:260px;overflow:auto;background:#fff;padding:12px;border:1px solid #ddd;display:none;"></pre>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_debug_log(): void {
        $log = WPGP_API_Client::get_debug_log();
        $count = count($log);
        ?>
        <div id="wpgp-debug-log" style="margin:12px 0 20px;background:#1e1e1e;color:#d4d4d4;border-radius:6px;font-family:monospace;font-size:12px;max-height:420px;overflow:auto;padding:0;">
            <div style="position:sticky;top:0;background:#2d2d2d;padding:8px 14px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #444;z-index:1;">
                <span style="color:#569cd6;font-weight:bold;">API Debug Log (<?php echo (int) $count; ?> calls)</span>
                <button type="button" onclick="if(confirm('Clear all logs?')){jQuery.post(ajaxurl,{action:'wpgp_clear_debug_log',nonce:'<?php echo wp_create_nonce('wpgp_clear_debug_log'); ?>'},function(){location.reload();});}" style="background:#c53030;color:#fff;border:none;padding:3px 10px;border-radius:3px;cursor:pointer;font-size:11px;">Clear</button>
            </div>
            <?php if (empty($log)) : ?>
                <div style="padding:14px;color:#888;">No API calls logged yet.</div>
            <?php else : ?>
                <?php foreach ($log as $i => $entry) : ?>
                    <?php
                    $status = (int) ($entry['status'] ?? 0);
                    $is_ok = $status >= 200 && $status < 300;
                    $badge_bg = $is_ok ? '#2e7d32' : ($status === 0 ? '#888' : '#c53030');
                    ?>
                    <div style="padding:10px 14px;border-bottom:1px solid #333;">
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                            <span style="background:<?php echo $badge_bg; ?>;color:#fff;padding:1px 8px;border-radius:3px;font-weight:bold;"><?php echo esc_html($entry['method']); ?> <?php echo (int) $entry['status']; ?></span>
                            <span style="color:#dcdcaa;word-break:break-all;"><?php echo esc_html($entry['url']); ?></span>
                            <span style="color:#888;margin-left:auto;white-space:nowrap;"><?php echo esc_html($entry['duration_ms']); ?>ms &middot; <?php echo esc_html($entry['time']); ?></span>
                        </div>
                        <?php if (!empty($entry['request_body'])) : ?>
                            <details style="margin-top:6px;">
                                <summary style="color:#9cdcfe;cursor:pointer;">Request Body</summary>
                                <pre style="margin:4px 0 0;padding:8px;background:#252526;border-radius:3px;overflow-x:auto;color:#ce9178;white-space:pre-wrap;word-break:break-all;"><?php echo esc_html($entry['request_body']); ?></pre>
                            </details>
                        <?php endif; ?>
                        <?php if (!empty($entry['response_body'])) : ?>
                            <details style="margin-top:4px;">
                                <summary style="color:#9cdcfe;cursor:pointer;">Response Body</summary>
                                <pre style="margin:4px 0 0;padding:8px;background:#252526;border-radius:3px;overflow-x:auto;color:#b5cea8;white-space:pre-wrap;word-break:break-all;"><?php echo esc_html($entry['response_body']); ?></pre>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}
