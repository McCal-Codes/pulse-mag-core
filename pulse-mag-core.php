<?php
/**
 * Plugin Name: Pulse Mag Core
 * Description: Core content model and editorial workflow defaults for Pulse Magazine.
 * Version: 0.1.5
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Pulse Magazine
 * Text Domain: pulse-mag-core
 * GitHub Plugin URI: https://github.com/McCal-Codes/pulse-mag-core
 * Primary Branch: main
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PULSE_MAG_CORE_PATH', plugin_dir_path(__FILE__));

require_once PULSE_MAG_CORE_PATH . 'src/post-types.php';
require_once PULSE_MAG_CORE_PATH . 'src/taxonomies.php';
require_once PULSE_MAG_CORE_PATH . 'src/meta.php';
require_once PULSE_MAG_CORE_PATH . 'src/editorial-workflow.php';
require_once PULSE_MAG_CORE_PATH . 'src/options.php';

function pulse_mag_core_activate(): void
{
    pulse_mag_register_post_types();
    pulse_mag_register_taxonomies();
    pulse_mag_add_editorial_roles();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'pulse_mag_core_activate');

function pulse_mag_core_deactivate(): void
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'pulse_mag_core_deactivate');
