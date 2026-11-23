<?php
if (!defined('ABSPATH')) { exit; }
final class NGC_Plugin {
 public static function init() {
  add_action('rest_api_init',array('NGC_REST','register'));
  add_action('admin_menu',array(__CLASS__,'menu'));
  add_shortcode('ng_clinic_booking',array(__CLASS__,'shortcode'));
  add_action('template_redirect',array(__CLASS__,'standalone'));
 }
 public static function menu() { add_menu_page(__('Clinic Studio','ng-clinic'),__('Clinic Studio','ng-clinic'),'ngc_manage','ng-clinic',array(__CLASS__,'admin'),'dashicons-heart',27); }
 public static function admin() { if(!current_user_can('ngc_manage')) wp_die(esc_html__('Access denied.','ng-clinic')); echo '<p><a class="button button-primary" href="'.esc_url(home_url('/?ng_clinic=1')).'">'.esc_html__('Open Clinic Studio','ng-clinic').'</a></p>'; echo self::app('staff'); }
 public static function assets($mode) {
  wp_enqueue_style('ngc-app',plugins_url('assets/app.css',NGC_FILE),array(),'1.0.0');
  wp_enqueue_script('ngc-app',plugins_url('assets/app.js',NGC_FILE),array(),'1.0.0',true);
  wp_localize_script('ngc-app','NGC',array('api'=>esc_url_raw(rest_url('ng-clinic/v1/')),'nonce'=>wp_create_nonce('wp_rest'),'bookingNonce'=>wp_create_nonce('ngc_booking'),'mode'=>$mode,'bookingUrl'=>home_url('/?ng_booking=1'),'staffUrl'=>home_url('/?ng_clinic=1'),'logoutUrl'=>wp_logout_url(home_url('/?ng_booking=1'))));
 }
 public static function app($mode) { self::assets($mode); return '<div id="ngc-app" class="ngc"><p class="ngc-loading">Loading Clinic Studio / جاري التحميل...</p><noscript>This interactive demonstration needs JavaScript. No booking has been made.</noscript></div>'; }
 public static function shortcode() { return self::app('booking'); }
 public static function standalone() {
  if(!isset($_GET['ng_clinic'])&&!isset($_GET['ng_booking'])) return;
  $mode=isset($_GET['ng_clinic'])?'staff':'booking';
  if($mode==='staff') { if(!is_user_logged_in()) { auth_redirect(); exit; } if(!current_user_can('ngc_manage')) wp_die(esc_html__('You do not have clinic access.','ng-clinic'),'',array('response'=>403)); }
  show_admin_bar(false); remove_action('wp_head','_admin_bar_bump_cb'); nocache_headers(); header('X-Robots-Tag: noindex, nofollow'); header('Referrer-Policy: same-origin');
  $app=self::app($mode); ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>NG Clinic Studio | Synthetic demo</title><?php wp_head(); ?></head><body style="margin:0"><?php echo $app; wp_footer(); ?></body></html><?php exit;
 }
}
