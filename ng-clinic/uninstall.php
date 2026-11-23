<?php
/** Removes only this demo's data when the plugin is deleted in WordPress. */
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }
global $wpdb;
foreach (array('appointments','patients','log') as $name) {
 // The table names are fixed plugin identifiers, never request input.
 $table = $wpdb->prefix . 'ngc_' . $name;
 $wpdb->query("DROP TABLE IF EXISTS `$table`");
}
delete_option('ngc_seeded');
remove_role('ngc_receptionist');
remove_role('ngc_clinician');
$admin = get_role('administrator');
if ($admin) { $admin->remove_cap('ngc_manage'); $admin->remove_cap('ngc_records'); }
