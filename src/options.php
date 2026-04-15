<?php
/**
 * Plugin settings and operational guidance.
 */

if (!defined('ABSPATH')) {
    exit;
}

function pulse_mag_default_settings(): array
{
    return [
        'contact_email' => '',
        'submissions_email' => '',
        'instagram_url' => '',
        'linkedin_url' => '',
        'bluesky_url' => '',
        'default_issue_status' => 'upcoming',
        'enable_author_profiles' => 1,
        'issues_archive_slug' => 'issues',
        'events_archive_slug' => 'events',
        'authors_archive_slug' => 'authors',
        'editorial_notice' => '',
    ];
}

function pulse_mag_get_settings(): array
{
    $saved = get_option('pulse_mag_core_settings', []);
    if (!is_array($saved)) {
        $saved = [];
    }
    return wp_parse_args($saved, pulse_mag_default_settings());
}

function pulse_mag_get_setting(string $key, $default = null)
{
    $settings = pulse_mag_get_settings();
    if (!array_key_exists($key, $settings)) {
        return $default;
    }
    return $settings[$key];
}

function pulse_mag_sanitize_settings(array $input): array
{
    $defaults = pulse_mag_default_settings();
    $output = $defaults;

    $output['contact_email'] = sanitize_email((string)($input['contact_email'] ?? ''));
    $output['submissions_email'] = sanitize_email((string)($input['submissions_email'] ?? ''));
    $output['instagram_url'] = esc_url_raw((string)($input['instagram_url'] ?? ''));
    $output['linkedin_url'] = esc_url_raw((string)($input['linkedin_url'] ?? ''));
    $output['bluesky_url'] = esc_url_raw((string)($input['bluesky_url'] ?? ''));

    $status = (string)($input['default_issue_status'] ?? 'upcoming');
    $allowed_statuses = ['current', 'upcoming', 'archived'];
    $output['default_issue_status'] = in_array($status, $allowed_statuses, true) ? $status : 'upcoming';

    $output['enable_author_profiles'] = empty($input['enable_author_profiles']) ? 0 : 1;
    $output['issues_archive_slug'] = sanitize_title((string)($input['issues_archive_slug'] ?? 'issues'));
    $output['events_archive_slug'] = sanitize_title((string)($input['events_archive_slug'] ?? 'events'));
    $output['authors_archive_slug'] = sanitize_title((string)($input['authors_archive_slug'] ?? 'authors'));
    $output['editorial_notice'] = sanitize_textarea_field((string)($input['editorial_notice'] ?? ''));

    if ($output['issues_archive_slug'] === '') {
        $output['issues_archive_slug'] = 'issues';
    }
    if ($output['events_archive_slug'] === '') {
        $output['events_archive_slug'] = 'events';
    }
    if ($output['authors_archive_slug'] === '') {
        $output['authors_archive_slug'] = 'authors';
    }

    return $output;
}

function pulse_mag_register_settings(): void
{
    register_setting(
        'pulse_mag_core_settings_group',
        'pulse_mag_core_settings',
        [
            'type' => 'array',
            'sanitize_callback' => 'pulse_mag_sanitize_settings',
            'default' => pulse_mag_default_settings(),
        ]
    );

    add_settings_section(
        'pulse_mag_editorial_section',
        __('Editorial Settings', 'pulse-mag-core'),
        '__return_false',
        'pulse-mag-core-settings'
    );

    add_settings_field(
        'default_issue_status',
        __('Default Issue Status', 'pulse-mag-core'),
        'pulse_mag_render_select_field',
        'pulse-mag-core-settings',
        'pulse_mag_editorial_section',
        [
            'key' => 'default_issue_status',
            'options' => [
                'current' => __('Current', 'pulse-mag-core'),
                'upcoming' => __('Upcoming', 'pulse-mag-core'),
                'archived' => __('Archived', 'pulse-mag-core'),
            ],
        ]
    );

    add_settings_field(
        'editorial_notice',
        __('Editorial Notice', 'pulse-mag-core'),
        'pulse_mag_render_textarea_field',
        'pulse-mag-core-settings',
        'pulse_mag_editorial_section',
        ['key' => 'editorial_notice']
    );

    add_settings_section(
        'pulse_mag_contact_section',
        __('Contact and Social', 'pulse-mag-core'),
        '__return_false',
        'pulse-mag-core-settings'
    );

    foreach ([
        'contact_email' => __('Contact Email', 'pulse-mag-core'),
        'submissions_email' => __('Submissions Email', 'pulse-mag-core'),
        'instagram_url' => __('Instagram URL', 'pulse-mag-core'),
        'linkedin_url' => __('LinkedIn URL', 'pulse-mag-core'),
        'bluesky_url' => __('Bluesky URL', 'pulse-mag-core'),
    ] as $key => $label) {
        add_settings_field(
            $key,
            $label,
            'pulse_mag_render_text_field',
            'pulse-mag-core-settings',
            'pulse_mag_contact_section',
            ['key' => $key]
        );
    }

    add_settings_section(
        'pulse_mag_structure_section',
        __('Content Structure', 'pulse-mag-core'),
        '__return_false',
        'pulse-mag-core-settings'
    );

    add_settings_field(
        'enable_author_profiles',
        __('Enable Author Profiles CPT', 'pulse-mag-core'),
        'pulse_mag_render_checkbox_field',
        'pulse-mag-core-settings',
        'pulse_mag_structure_section',
        ['key' => 'enable_author_profiles']
    );

    foreach ([
        'issues_archive_slug' => __('Issues Archive Slug', 'pulse-mag-core'),
        'events_archive_slug' => __('Events Archive Slug', 'pulse-mag-core'),
        'authors_archive_slug' => __('Authors Archive Slug', 'pulse-mag-core'),
    ] as $key => $label) {
        add_settings_field(
            $key,
            $label,
            'pulse_mag_render_text_field',
            'pulse-mag-core-settings',
            'pulse_mag_structure_section',
            ['key' => $key]
        );
    }
}
add_action('admin_init', 'pulse_mag_register_settings');

function pulse_mag_register_settings_page(): void
{
    add_options_page(
        __('Pulse Mag Core', 'pulse-mag-core'),
        __('Pulse Mag Core', 'pulse-mag-core'),
        'manage_options',
        'pulse-mag-core-settings',
        'pulse_mag_render_settings_page'
    );

    add_submenu_page(
        'options-general.php',
        __('Pulse Editorial Manual', 'pulse-mag-core'),
        __('Pulse Editorial Manual', 'pulse-mag-core'),
        'edit_posts',
        'pulse-mag-editorial-manual',
        'pulse_mag_render_editorial_manual_page'
    );
}
add_action('admin_menu', 'pulse_mag_register_settings_page');

function pulse_mag_render_text_field(array $args): void
{
    $key = (string)$args['key'];
    $value = (string)pulse_mag_get_setting($key, '');
    printf(
        '<input type="text" class="regular-text" name="pulse_mag_core_settings[%1$s]" value="%2$s" />',
        esc_attr($key),
        esc_attr($value)
    );
}

function pulse_mag_render_textarea_field(array $args): void
{
    $key = (string)$args['key'];
    $value = (string)pulse_mag_get_setting($key, '');
    printf(
        '<textarea class="large-text" rows="4" name="pulse_mag_core_settings[%1$s]">%2$s</textarea>',
        esc_attr($key),
        esc_textarea($value)
    );
}

function pulse_mag_render_checkbox_field(array $args): void
{
    $key = (string)$args['key'];
    $checked = (int)pulse_mag_get_setting($key, 0) === 1 ? 'checked' : '';
    printf(
        '<label><input type="checkbox" name="pulse_mag_core_settings[%1$s]" value="1" %2$s /> %3$s</label>',
        esc_attr($key),
        $checked,
        esc_html__('Enabled', 'pulse-mag-core')
    );
}

function pulse_mag_render_select_field(array $args): void
{
    $key = (string)$args['key'];
    $options = is_array($args['options'] ?? null) ? $args['options'] : [];
    $current = (string)pulse_mag_get_setting($key, '');

    printf('<select name="pulse_mag_core_settings[%s]">', esc_attr($key));
    foreach ($options as $value => $label) {
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr((string)$value),
            selected($current, (string)$value, false),
            esc_html((string)$label)
        );
    }
    echo '</select>';
}

function pulse_mag_render_settings_page(): void
{
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Pulse Mag Core Settings', 'pulse-mag-core'); ?></h1>
        <p><?php esc_html_e('Manage editorial defaults, content structure, and operational contacts.', 'pulse-mag-core'); ?></p>

        <form method="post" action="options.php">
            <?php
            settings_fields('pulse_mag_core_settings_group');
            do_settings_sections('pulse-mag-core-settings');
            submit_button(__('Save Settings', 'pulse-mag-core'));
            ?>
        </form>

        <hr />

        <h2><?php esc_html_e('Operations Checklist', 'pulse-mag-core'); ?></h2>
        <ul style="list-style: disc; padding-left: 20px;">
            <li><?php esc_html_e('SEO: use Pulse SEO unless you standardize on Yoast SEO, Rank Math, or AIOSEO (only one should output meta).', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Caching (WP Super Cache, LiteSpeed Cache, or host cache)', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Backups (UpdraftPlus or host snapshots)', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Security hardening (Wordfence or host firewall)', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Image optimization (ShortPixel, Imagify, or host media optimizer)', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Forms (WPForms or Gravity Forms)', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Analytics integration (Site Kit or GA4 plugin)', 'pulse-mag-core'); ?></li>
        </ul>

        <p><em><?php esc_html_e('If you change archive slugs, re-save Permalinks in Settings -> Permalinks.', 'pulse-mag-core'); ?></em></p>
    </div>
    <?php
}

function pulse_mag_render_editorial_manual_page(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('You do not have permission to view this page.', 'pulse-mag-core'));
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Pulse Writer + Editor Manual', 'pulse-mag-core'); ?></h1>
        <p><?php esc_html_e('Day-to-day publishing guide for writers, section editors, and managing editors.', 'pulse-mag-core'); ?></p>

        <h2><?php esc_html_e('What to Publish Where', 'pulse-mag-core'); ?></h2>
        <ul style="list-style: disc; padding-left: 20px;">
            <li><?php esc_html_e('Post = Pulse News article (/blog/)', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Issue = annual issue entry (/issues/)', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Event = readings/workshops/calendar item (/events/)', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Page = evergreen page (/about/, /submit/, /join/)', 'pulse-mag-core'); ?></li>
        </ul>

        <h2><?php esc_html_e('Writer Workflow', 'pulse-mag-core'); ?></h2>
        <ol style="padding-left: 20px;">
            <li><?php esc_html_e('Create draft in Posts, Issues, or Events.', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Add headline, excerpt, featured image (with alt text), and body content.', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Set categories/section and meaningful tags where applicable.', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('In Pulse SEO box, optionally set title/description overrides; use noindex only when instructed.', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Save draft and hand off to editor for review.', 'pulse-mag-core'); ?></li>
        </ol>

        <h2><?php esc_html_e('Editor Review Checklist', 'pulse-mag-core'); ?></h2>
        <ul style="list-style: disc; padding-left: 20px;">
            <li><?php esc_html_e('Title quality and clarity', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Excerpt matches article intent', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Featured image present with alt text', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Links, names, dates, and credits verified', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('SEO fields sensible and concise', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Preview checked before scheduling/publish', 'pulse-mag-core'); ?></li>
        </ul>

        <h2><?php esc_html_e('Issue Publishing Workflow', 'pulse-mag-core'); ?></h2>
        <ol style="padding-left: 20px;">
            <li><?php esc_html_e('Create issue and complete season, status, issue number, summary, and PDF URL fields.', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Set cover image and alt text.', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Status flow: upcoming -> current on launch, previous current -> archived.', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('After publish, verify issue appears in archive and single issue page.', 'pulse-mag-core'); ?></li>
        </ol>

        <h2><?php esc_html_e('Event Publishing Workflow', 'pulse-mag-core'); ?></h2>
        <ol style="padding-left: 20px;">
            <li><?php esc_html_e('Create event with date, location, RSVP/info link, summary, and featured image.', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Publish and verify visibility in /events/.', 'pulse-mag-core'); ?></li>
        </ol>

        <h2><?php esc_html_e('Common Fixes', 'pulse-mag-core'); ?></h2>
        <ul style="list-style: disc; padding-left: 20px;">
            <li><?php esc_html_e('404 after slug/content-type changes: Settings -> Permalinks -> Save Changes.', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Layout mismatch: clear cache and hard refresh browser.', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Duplicate SEO output warning: ensure only one SEO output system is active.', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Missing social image: set featured image or default OG image in Pulse SEO.', 'pulse-mag-core'); ?></li>
        </ul>

        <h2><?php esc_html_e('Escalate To Admin When', 'pulse-mag-core'); ?></h2>
        <ul style="list-style: disc; padding-left: 20px;">
            <li><?php esc_html_e('Global slugs/permalinks are changing', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Theme templates break after Site Editor changes', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Plugin updates affect publishing behavior', 'pulse-mag-core'); ?></li>
            <li><?php esc_html_e('Redirect loops or recurring 404s appear', 'pulse-mag-core'); ?></li>
        </ul>

        <p><em><?php esc_html_e('Long-form version also lives in wordpress/docs/writer-editor-manual.md in the repository.', 'pulse-mag-core'); ?></em></p>
    </div>
    <?php
}

function pulse_mag_register_dashboard_widget(): void
{
    if (!current_user_can('edit_posts')) {
        return;
    }

    wp_add_dashboard_widget(
        'pulse_mag_today_checklist',
        __('Today\'s Publishing Checklist', 'pulse-mag-core'),
        'pulse_mag_render_dashboard_widget'
    );
}
add_action('wp_dashboard_setup', 'pulse_mag_register_dashboard_widget');

function pulse_mag_render_dashboard_widget(): void
{
    ?>
    <p><?php esc_html_e('Use this quick list before publishing or scheduling content.', 'pulse-mag-core'); ?></p>
    <ol style="padding-left: 20px; margin-bottom: 12px;">
        <li><?php esc_html_e('Headline and excerpt are clear and accurate.', 'pulse-mag-core'); ?></li>
        <li><?php esc_html_e('Featured image is present and has alt text.', 'pulse-mag-core'); ?></li>
        <li><?php esc_html_e('Links, names, dates, and credits are verified.', 'pulse-mag-core'); ?></li>
        <li><?php esc_html_e('Pulse SEO fields are reviewed (title/description/noindex).', 'pulse-mag-core'); ?></li>
        <li><?php esc_html_e('Preview checked before schedule/publish.', 'pulse-mag-core'); ?></li>
        <li><?php esc_html_e('After publish: confirm content appears in archive and nav links work.', 'pulse-mag-core'); ?></li>
    </ol>
    <p>
        <a class="button button-secondary" href="<?php echo esc_url(admin_url('options-general.php?page=pulse-mag-editorial-manual')); ?>">
            <?php esc_html_e('Open Full Editorial Manual', 'pulse-mag-core'); ?>
        </a>
    </p>
    <?php
}

/**
 * After slug or author-profile toggles change, flush rewrite rules so archives resolve.
 *
 * @param mixed $old_value
 * @param mixed $value
 */
function pulse_mag_maybe_flush_rewrites_on_settings_update($old_value, $value): void
{
    if (!is_array($value)) {
        return;
    }
    if (!is_array($old_value)) {
        $old_value = pulse_mag_default_settings();
    }
    $slug_keys = ['issues_archive_slug', 'events_archive_slug', 'authors_archive_slug'];
    foreach ($slug_keys as $key) {
        $old = (string) ($old_value[$key] ?? '');
        $new = (string) ($value[$key] ?? '');
        if ($old !== $new) {
            flush_rewrite_rules(false);
            return;
        }
    }
    $old_en = (int) ($old_value['enable_author_profiles'] ?? 0);
    $new_en = (int) ($value['enable_author_profiles'] ?? 0);
    if ($old_en !== $new_en) {
        flush_rewrite_rules(false);
    }
}
add_action('update_option_pulse_mag_core_settings', 'pulse_mag_maybe_flush_rewrites_on_settings_update', 10, 2);
