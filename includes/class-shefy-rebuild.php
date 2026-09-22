<?php

if (!defined('ABSPATH')) {
    exit;
}

class Shefy_Rebuild {
    const OPTION_SNAPSHOT = 'shefy_menu_snapshot';
    const OPTION_NOTES    = 'shefy_tonights_notes';

    public static function rebuild() {
        if (!function_exists('wc_get_products')) {
            return new WP_Error('woocommerce_unavailable', 'WooCommerce is unavailable.');
        }

        $product_ids = wc_get_products([
            'status' => 'publish',
            'limit'  => SHEFY_MAX_ITEMS + 1,
            'return' => 'ids',
            'orderby'=> 'title',
            'order'  => 'ASC',
        ]);

        if (count($product_ids) > SHEFY_MAX_ITEMS) {
            return new WP_Error(
                'menu_too_large',
                sprintf(
                    'Shefy v0.1 supports up to %d published products. This store currently has more than %d.',
                    SHEFY_MAX_ITEMS,
                    SHEFY_MAX_ITEMS
                )
            );
        }

        $items = [];

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);

            if (!$product) {
                continue;
            }

            // Variations stay attached to their parent product. The AI sees the meaningful menu item.
            if ($product->is_type('variation')) {
                continue;
            }

            $category_names = wp_get_post_terms(
                $product_id,
                'product_cat',
                ['fields' => 'names']
            );

            if (is_wp_error($category_names)) {
                $category_names = [];
            }

            $items[] = [
                'product_id'        => $product->get_id(),
                'name'              => wp_strip_all_tags($product->get_name()),
                'short_description' => wp_strip_all_tags($product->get_short_description()),
                'price'             => (string) $product->get_price(),
                'price_html'        => wp_strip_all_tags($product->get_price_html()),
                'categories'        => array_values($category_names),
                'in_stock'          => $product->is_in_stock(),
                'purchasable'       => $product->is_purchasable(),
                'type'              => $product->get_type(),
                'permalink'         => get_permalink($product_id),
            ];
        }

        $snapshot = [
            'version'      => time(),
            'generated_at' => current_time('mysql', true),
            'item_count'   => count($items),
            'max_items'    => SHEFY_MAX_ITEMS,
            'items'        => $items,
        ];

        update_option(self::OPTION_SNAPSHOT, $snapshot, false);

        return $snapshot;
    }

    public static function get_snapshot() {
        $snapshot = get_option(self::OPTION_SNAPSHOT, []);
        return is_array($snapshot) ? $snapshot : [];
    }

    public static function get_notes() {
        return (string) get_option(self::OPTION_NOTES, '');
    }

    public static function set_notes($notes) {
        update_option(
            self::OPTION_NOTES,
            sanitize_textarea_field($notes),
            false
        );
    }
}
