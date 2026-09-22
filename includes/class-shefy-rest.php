<?php

if (!defined('ABSPATH')) {
    exit;
}

class Shefy_REST {
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'routes']);
    }

    public static function routes() {
        register_rest_route('shefy/v1', '/chat', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [__CLASS__, 'chat'],
            'permission_callback' => '__return_true',
            'args'                => [
                'message' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);
    }

    public static function chat(WP_REST_Request $request) {
        $message  = trim((string) $request->get_param('message'));
        $snapshot = Shefy_Rebuild::get_snapshot();

        if ($message === '') {
            return new WP_Error('empty_message', 'Message cannot be empty.', ['status' => 400]);
        }

        if (empty($snapshot['items'])) {
            return new WP_Error(
                'menu_not_built',
                'The Shefy menu snapshot has not been built yet.',
                ['status' => 503]
            );
        }

$result = Shefy_OpenAI::chat(
    $message,
    $snapshot,
    Shefy_Rebuild::get_notes()
);

if (is_wp_error($result)) {
    return $result;
}

return rest_ensure_response($result);







    }
}
