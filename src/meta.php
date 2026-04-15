<?php
/**
 * Register metadata for custom post types.
 */

if (!defined('ABSPATH')) {
    exit;
}

function pulse_mag_register_meta_fields(): void
{
    $issue_fields = [
        '_pulse_issue_season' => 'string',
        '_pulse_issue_status' => 'string',
        '_pulse_issue_number' => 'integer',
        '_pulse_issue_summary' => 'string',
        '_pulse_issue_window_text' => 'string',
        '_pulse_issue_status_note' => 'string',
        '_pulse_issue_pdf_url' => 'string',
        '_pulse_issue_pdf_attachment_id' => 'integer',
    ];

    foreach ($issue_fields as $key => $type) {
        $sanitize = 'sanitize_text_field';
        if ($type === 'integer') {
            $sanitize = 'absint';
        } elseif ($key === '_pulse_issue_pdf_url') {
            $sanitize = 'esc_url_raw';
        }

        register_post_meta('issue', $key, [
            'show_in_rest' => true,
            'single' => true,
            'type' => $type,
            'sanitize_callback' => $sanitize,
            'auth_callback' => fn() => current_user_can('edit_posts'),
        ]);
    }

    $event_fields = [
        '_pulse_event_date' => 'string',
        '_pulse_event_location' => 'string',
        '_pulse_event_link' => 'string',
        '_pulse_event_summary' => 'string',
    ];

    foreach ($event_fields as $key => $type) {
        register_post_meta('event', $key, [
            'show_in_rest' => true,
            'single' => true,
            'type' => $type,
            'sanitize_callback' => $key === '_pulse_event_link' ? 'esc_url_raw' : 'sanitize_text_field',
            'auth_callback' => fn() => current_user_can('edit_posts'),
        ]);
    }
}
add_action('init', 'pulse_mag_register_meta_fields');
