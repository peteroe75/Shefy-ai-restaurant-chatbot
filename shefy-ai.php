<?php
/**
 * Plugin Name: Shefy AI
 * Description: Lightweight AI waiter foundation for WooCommerce restaurants.
 * Version: 0.1.0
 * Author: Meadowlark IT
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SHEFY_VERSION', '0.1.0');
define('SHEFY_PATH', plugin_dir_path(__FILE__));
define('SHEFY_URL', plugin_dir_url(__FILE__));
define('SHEFY_MAX_ITEMS', 100);

require_once SHEFY_PATH . 'includes/class-shefy-rebuild.php';
require_once SHEFY_PATH . 'includes/class-shefy-admin.php';
require_once SHEFY_PATH . 'includes/class-shefy-openai.php';
require_once SHEFY_PATH . 'includes/class-shefy-rest.php';

final class Shefy_AI {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
        add_shortcode('shefy_waiter', [$this, 'render_waiter']);
    }

    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return;
        }

        Shefy_Admin::init();
        Shefy_REST::init();
    }

    public function render_waiter() {
        if (!class_exists('WooCommerce')) {
            return '';
        }


wp_enqueue_script(
    'shefy-products',
    SHEFY_URL . 'assets/shefy-products.js',
    [],
    SHEFY_VERSION,
    true
);


        wp_enqueue_style(
            'shefy-ai',
            SHEFY_URL . 'assets/shefy.css',
            [],
            SHEFY_VERSION
        );

        wp_enqueue_script(
            'shefy-ai',
            SHEFY_URL . 'assets/shefy.js',
            [],
            SHEFY_VERSION,
            true
        );

wp_enqueue_style(
    'shefy-products',
    SHEFY_URL . 'assets/shefy-products.css',
    [],
    SHEFY_VERSION
);


        wp_localize_script('shefy-ai', 'ShefyConfig', [
            'restUrl' => esc_url_raw(rest_url('shefy/v1/chat')),
            'nonce'   => wp_create_nonce('wp_rest'),
        ]);

        ob_start();
        ?>
        <div class="shefy" data-shefy>
            <div class="shefy__messages" data-shefy-messages>
                <div class="shefy__message shefy__message--assistant">
                    Hi — I’m Shefy. Ask me about the menu.
                </div>
            </div>

            <form class="shefy__form" data-shefy-form>
                <label class="screen-reader-text" for="shefy-message">Message</label>
                <input
                    id="shefy-message"
                    class="shefy__input"
                    type="text"
                    autocomplete="off"
                    placeholder="What sounds good tonight?"
                    data-shefy-input
                >
                <button class="shefy__send" type="submit">Send</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('Shefy AI requires WooCommerce.', 'shefy-ai');
        echo '</p></div>';
    }
}

Shefy_AI::instance();
