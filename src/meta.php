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

    $author_fields = [
        '_pulse_author_role' => 'string',
        '_pulse_author_pronouns' => 'string',
        '_pulse_author_looking_for' => 'string',
    ];

    foreach ($author_fields as $key => $type) {
        register_post_meta('author_profile', $key, [
            'show_in_rest' => true,
            'single' => true,
            'type' => $type,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => fn() => current_user_can('edit_posts'),
        ]);
    }
}
add_action('init', 'pulse_mag_register_meta_fields');

/**
 * Add editor fields for Author Profile metadata.
 */
function pulse_mag_add_author_profile_meta_box(): void
{
    add_meta_box(
        'pulse-author-profile-meta',
        __('Author Profile Details', 'pulse-mag-core'),
        'pulse_mag_render_author_profile_meta_box',
        'author_profile',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'pulse_mag_add_author_profile_meta_box');

/**
 * Render Author Profile meta box fields.
 */
function pulse_mag_render_author_profile_meta_box(\WP_Post $post): void
{
    wp_nonce_field('pulse_author_profile_meta_nonce', 'pulse_author_profile_meta_nonce');

    $role = (string) get_post_meta($post->ID, '_pulse_author_role', true);
    $pronouns = (string) get_post_meta($post->ID, '_pulse_author_pronouns', true);
    $looking_for = (string) get_post_meta($post->ID, '_pulse_author_looking_for', true);
    ?>
    <p>
        <label for="pulse_author_role"><strong><?php esc_html_e('Role', 'pulse-mag-core'); ?></strong></label><br />
        <input
            type="text"
            id="pulse_author_role"
            name="pulse_author_role"
            value="<?php echo esc_attr($role); ?>"
            class="widefat"
            placeholder="<?php echo esc_attr__('Poetry Editor', 'pulse-mag-core'); ?>"
        />
    </p>
    <p>
        <label for="pulse_author_pronouns"><strong><?php esc_html_e('Pronouns', 'pulse-mag-core'); ?></strong></label><br />
        <input
            type="text"
            id="pulse_author_pronouns"
            name="pulse_author_pronouns"
            value="<?php echo esc_attr($pronouns); ?>"
            class="widefat"
            placeholder="<?php echo esc_attr__('she/her, he/him, they/them', 'pulse-mag-core'); ?>"
        />
    </p>
    <p>
        <label for="pulse_author_looking_for"><strong><?php esc_html_e('Looking For', 'pulse-mag-core'); ?></strong></label><br />
        <textarea
            id="pulse_author_looking_for"
            name="pulse_author_looking_for"
            class="widefat"
            rows="3"
            placeholder="<?php echo esc_attr__('What this editor looks for in submissions.', 'pulse-mag-core'); ?>"
        ><?php echo esc_textarea($looking_for); ?></textarea>
    </p>
    <?php
}

/**
 * Save Author Profile meta box fields.
 */
function pulse_mag_save_author_profile_meta(int $post_id): void
{
    if (!isset($_POST['pulse_author_profile_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pulse_author_profile_meta_nonce'])), 'pulse_author_profile_meta_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (get_post_type($post_id) !== 'author_profile') {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $role = isset($_POST['pulse_author_role']) ? sanitize_text_field(wp_unslash($_POST['pulse_author_role'])) : '';
    $pronouns = isset($_POST['pulse_author_pronouns']) ? sanitize_text_field(wp_unslash($_POST['pulse_author_pronouns'])) : '';
    $looking_for = isset($_POST['pulse_author_looking_for']) ? sanitize_text_field(wp_unslash($_POST['pulse_author_looking_for'])) : '';

    update_post_meta($post_id, '_pulse_author_role', $role);
    update_post_meta($post_id, '_pulse_author_pronouns', $pronouns);
    update_post_meta($post_id, '_pulse_author_looking_for', $looking_for);
}
add_action('save_post_author_profile', 'pulse_mag_save_author_profile_meta');

/**
 * Resolve author profile meta by display key.
 */
function pulse_mag_get_author_meta_by_key(int $post_id, string $field): string
{
    $field_map = [
        'role' => '_pulse_author_role',
        'pronouns' => '_pulse_author_pronouns',
        'looking_for' => '_pulse_author_looking_for',
    ];

    $meta_key = $field_map[$field] ?? '';
    if ($meta_key === '') {
        return '';
    }

    return (string) get_post_meta($post_id, $meta_key, true);
}

/**
 * Shortcode: [pulse_author_meta field="role|pronouns|looking_for" label="Label"].
 */
function pulse_mag_author_meta_shortcode(array $atts = []): string
{
    $atts = shortcode_atts([
        'field' => '',
        'label' => '',
        'post_id' => 0,
    ], $atts, 'pulse_author_meta');

    $field = sanitize_key((string) $atts['field']);
    $post_id = absint($atts['post_id']);
    if ($post_id <= 0) {
        $post_id = get_the_ID() ?: 0;
    }

    if ($post_id <= 0 || get_post_type($post_id) !== 'author_profile') {
        return '';
    }

    $value = pulse_mag_get_author_meta_by_key($post_id, $field);
    if ($value === '') {
        return '';
    }

    $label = sanitize_text_field((string) $atts['label']);
    if ($label !== '') {
        return sprintf(
            '<p class="pulse-author-meta pulse-author-meta--%1$s"><strong>%2$s:</strong> %3$s</p>',
            esc_attr($field),
            esc_html($label),
            esc_html($value)
        );
    }

    return sprintf(
        '<p class="pulse-author-meta pulse-author-meta--%1$s">%2$s</p>',
        esc_attr($field),
        esc_html($value)
    );
}
add_shortcode('pulse_author_meta', 'pulse_mag_author_meta_shortcode');
