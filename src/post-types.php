<?php
/**
 * Register custom post types.
 */

if (!defined('ABSPATH')) {
    exit;
}

function pulse_mag_register_post_types(): void
{
    $issue_slug = (string)pulse_mag_get_setting('issues_archive_slug', 'issues');
    $event_slug = (string)pulse_mag_get_setting('events_archive_slug', 'events');
    $author_slug = (string)pulse_mag_get_setting('authors_archive_slug', 'authors');
    $enable_author_profiles = (int)pulse_mag_get_setting('enable_author_profiles', 1) === 1;

    register_post_type('issue', [
        'labels' => [
            'name' => __('Issues', 'pulse-mag-core'),
            'singular_name' => __('Issue', 'pulse-mag-core'),
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-book',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'],
        'has_archive' => true,
        'rewrite' => ['slug' => $issue_slug],
    ]);

    register_post_type('event', [
        'labels' => [
            'name' => __('Events', 'pulse-mag-core'),
            'singular_name' => __('Event', 'pulse-mag-core'),
        ],
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'],
        'has_archive' => true,
        'rewrite' => ['slug' => $event_slug],
    ]);

    if ($enable_author_profiles) {
        register_post_type('author_profile', [
            'labels' => [
                'name' => __('Author Profiles', 'pulse-mag-core'),
                'singular_name' => __('Author Profile', 'pulse-mag-core'),
            ],
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-id-alt',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'custom-fields'],
            'has_archive' => true,
            'rewrite' => ['slug' => $author_slug],
        ]);
    }
}
add_action('init', 'pulse_mag_register_post_types');
