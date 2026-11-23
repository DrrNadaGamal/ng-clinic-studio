<?php
/**
 * Plugin Name: NG Clinic Studio
 * Description: Synthetic-data clinic workflow demonstration. Not for clinical use.
 * Version: 1.0.0
 * Author: Nada Gamal (direction), AI-assisted implementation
 * License: GPL-2.0-or-later
 * Text Domain: ng-clinic
 * Requires PHP: 7.4
 * Requires at least: 6.4
 */
if (!defined('ABSPATH')) { exit; }
define('NGC_FILE', __FILE__);
foreach (array('store','rest','plugin') as $ngc_class) { require_once __DIR__ . '/includes/class-' . $ngc_class . '.php'; }
register_activation_hook(__FILE__, array('NGC_Store','activate'));
add_action('plugins_loaded', array('NGC_Plugin','init'));
