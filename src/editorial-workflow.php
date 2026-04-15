<?php
/**
 * Editorial workflow defaults, roles, and status checks.
 */

if (!defined('ABSPATH')) {
    exit;
}

function pulse_mag_add_editorial_roles(): void
{
    add_role('pulse_author', __('Pulse Author', 'pulse-mag-core'), [
        'read' => true,
        'edit_posts' => true,
        'upload_files' => true,
    ]);

    add_role('pulse_editor', __('Pulse Editor', 'pulse-mag-core'), [
        'read' => true,
        'edit_posts' => true,
        'edit_others_posts' => true,
        'publish_posts' => true,
        'moderate_comments' => true,
        'upload_files' => true,
        // Required for Custom HTML/code blocks in the block editor.
        'unfiltered_html' => true,
    ]);
}

/**
 * Keep custom editorial role caps aligned even on existing installs.
 */
function pulse_mag_sync_editorial_role_caps(): void
{
    $author = get_role('pulse_author');
    if ($author instanceof \WP_Role && !$author->has_cap('upload_files')) {
        $author->add_cap('upload_files');
    }

    $editor = get_role('pulse_editor');
    if ($editor instanceof \WP_Role) {
        if (!$editor->has_cap('upload_files')) {
            $editor->add_cap('upload_files');
        }
        if (!$editor->has_cap('unfiltered_html')) {
            $editor->add_cap('unfiltered_html');
        }
    }
}
add_action('init', 'pulse_mag_sync_editorial_role_caps', 11);

function pulse_mag_require_review_for_publish(array $data, array $postarr): array
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $data;
    }
    if (defined('DOING_CRON') && DOING_CRON) {
        return $data;
    }
    if (defined('WP_IMPORTING') && WP_IMPORTING) {
        return $data;
    }
    if (defined('WP_CLI') && WP_CLI) {
        return $data;
    }

    if (($data['post_status'] ?? '') !== 'publish') {
        return $data;
    }

    $post_id = isset($postarr['ID']) ? (int) $postarr['ID'] : 0;
    if ($post_id > 0) {
        $prev = get_post_status($post_id);
        if ($prev === 'publish' || $prev === 'private') {
            return $data;
        }
    }

    if (current_user_can('manage_options') || current_user_can('publish_pages')) {
        return $data;
    }

    if (current_user_can('edit_others_posts') || current_user_can('publish_posts')) {
        return $data;
    }

    // Force non-editorial roles into pending for review (classic admin, block editor / REST, etc.).
    $data['post_status'] = 'pending';
    return $data;
}
add_filter('wp_insert_post_data', 'pulse_mag_require_review_for_publish', 10, 2);
