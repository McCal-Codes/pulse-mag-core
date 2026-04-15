<?php
/**
 * Register custom taxonomies.
 */

if (!defined('ABSPATH')) {
    exit;
}

function pulse_mag_register_taxonomies(): void
{
    register_taxonomy('section', ['post', 'issue', 'event'], [
        'labels' => [
            'name' => __('Sections', 'pulse-mag-core'),
            'singular_name' => __('Section', 'pulse-mag-core'),
        ],
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'section'],
    ]);

    register_taxonomy('issue_volume', ['issue'], [
        'labels' => [
            'name' => __('Issue Volumes', 'pulse-mag-core'),
            'singular_name' => __('Issue Volume', 'pulse-mag-core'),
        ],
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'rewrite' => ['slug' => 'issue-volume'],
    ]);

    register_taxonomy('topic', ['post', 'issue', 'event'], [
        'labels' => [
            'name' => __('Topics', 'pulse-mag-core'),
            'singular_name' => __('Topic', 'pulse-mag-core'),
        ],
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'rewrite' => ['slug' => 'topic'],
    ]);
}
add_action('init', 'pulse_mag_register_taxonomies');
