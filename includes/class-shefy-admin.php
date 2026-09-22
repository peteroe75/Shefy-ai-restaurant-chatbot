<?php

if (!defined('ABSPATH')) {
    exit;
}

class Shefy_Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_shefy_rebuild', [__CLASS__, 'handle_rebuild']);
        add_action('admin_post_shefy_save_notes', [__CLASS__, 'handle_save_notes']);
    }

    public static function menu() {
        add_menu_page(
            'Shefy',
            'Shefy',
            'manage_woocommerce',
            'shefy-ai',
            [__CLASS__, 'page'],
            'dashicons-food',
            56
        );
    }

    public static function handle_rebuild() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Not allowed.');
        }

        check_admin_referer('shefy_rebuild');

        $result = Shefy_Rebuild::rebuild();

        $args = ['page' => 'shefy-ai'];

        if (is_wp_error($result)) {
            $args['shefy_error'] = rawurlencode($result->get_error_message());
        } else {
            $args['shefy_rebuilt'] = 1;
        }

        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }

    public static function handle_save_notes() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Not allowed.');
        }

        check_admin_referer('shefy_save_notes');

        Shefy_Rebuild::set_notes(
            isset($_POST['shefy_tonights_notes'])
                ? wp_unslash($_POST['shefy_tonights_notes'])
                : ''
        );

        wp_safe_redirect(
            add_query_arg(
                ['page' => 'shefy-ai', 'shefy_notes_saved' => 1],
                admin_url('admin.php')
            )
        );
        exit;
    }

    public static function page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $snapshot = Shefy_Rebuild::get_snapshot();
        $count    = isset($snapshot['item_count']) ? (int) $snapshot['item_count'] : 0;
        $built    = !empty($snapshot['generated_at']) ? $snapshot['generated_at'] : 'Never';
        $notes    = Shefy_Rebuild::get_notes();
        ?>
        <div class="wrap">
            <h1>Shefy</h1>

            <?php if (!empty($_GET['shefy_rebuilt'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Menu snapshot rebuilt.</p></div>
            <?php endif; ?>

            <?php if (!empty($_GET['shefy_notes_saved'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Tonight's notes saved.</p></div>
            <?php endif; ?>

            <?php if (!empty($_GET['shefy_error'])) : ?>
                <div class="notice notice-error"><p><?php echo esc_html(rawurldecode(wp_unslash($_GET['shefy_error']))); ?></p></div>
            <?php endif; ?>

            <table class="widefat striped" style="max-width:760px;margin-top:18px;">
                <tbody>
                    <tr>
                        <td><strong>Menu items</strong></td>
                        <td><?php echo esc_html($count . ' / ' . SHEFY_MAX_ITEMS); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Context mode</strong></td>
                        <td>Full menu</td>
                    </tr>
                    <tr>
                        <td><strong>Last rebuild</strong></td>
                        <td><?php echo esc_html($built); ?></td>
                    </tr>
                </tbody>
            </table>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:18px;">
                <input type="hidden" name="action" value="shefy_rebuild">
                <?php wp_nonce_field('shefy_rebuild'); ?>
                <?php submit_button('Rebuild Menu', 'primary', 'submit', false); ?>
            </form>

            <hr style="margin:32px 0;max-width:760px;">

            <h2>Tonight's Notes</h2>
            <p style="max-width:760px;">
                Temporary service information for Shefy, such as specials, sold-out items, or what the kitchen wants to feature.
            </p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="shefy_save_notes">
                <?php wp_nonce_field('shefy_save_notes'); ?>
                <textarea
                    name="shefy_tonights_notes"
                    rows="8"
                    class="large-text"
                    style="max-width:760px;"
                    placeholder="Example: We're out of salmon. Tonight's special is braised short rib over polenta for $26."
                ><?php echo esc_textarea($notes); ?></textarea>
                <p><?php submit_button('Save Tonight\'s Notes', 'secondary', 'submit', false); ?></p>
            </form>

            <hr style="margin:32px 0;max-width:760px;">

            <h2>Frontend</h2>
            <p>Add this shortcode to any page:</p>
            <code>[shefy_waiter]</code>
            <p><em>v0.1 currently returns a test response from the REST endpoint. The AI call is the next file to add.</em></p>
        </div>
        <?php
    }
}
