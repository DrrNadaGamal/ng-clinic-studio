<?php
if (!defined('ABSPATH')) { exit; }
final class NGC_REST {
 public static function register() {
  register_rest_route('ng-clinic/v1','/catalog',array('methods'=>'GET','callback'=>array(__CLASS__,'catalog'),'permission_callback'=>'__return_true'));
  register_rest_route('ng-clinic/v1','/book',array('methods'=>'POST','callback'=>array(__CLASS__,'book'),'permission_callback'=>array(__CLASS__,'booking_permission')));
  register_rest_route('ng-clinic/v1','/workspace',array('methods'=>'GET','callback'=>array(__CLASS__,'workspace'),'permission_callback'=>array(__CLASS__,'staff')));
  register_rest_route('ng-clinic/v1','/appointments/(?P<id>\d+)',array('methods'=>'PATCH','callback'=>array(__CLASS__,'status'),'permission_callback'=>array(__CLASS__,'staff')));
  register_rest_route('ng-clinic/v1','/patients/(?P<id>\d+)',array('methods'=>'PATCH','callback'=>array(__CLASS__,'notes'),'permission_callback'=>array(__CLASS__,'clinical')));
 }
 public static function staff() { return current_user_can('ngc_manage'); }
 public static function clinical() { return current_user_can('ngc_records'); }
 public static function error($code,$message,$status=400) { return new WP_Error($code,$message,array('status'=>$status)); }
 public static function booking_permission($r) { return wp_verify_nonce($r->get_header('X-NGC-Nonce'),'ngc_booking') ? true : self::error('nonce','Reload the page before booking.',403); }
 public static function catalog($r) { $date=sanitize_text_field($r->get_param('date')?:current_datetime()->format('Y-m-d')); if(!NGC_Store::allowed_date($date)) return self::error('date','Choose a date in the next 60 days.'); return rest_ensure_response(NGC_Store::public_catalog($date)); }
 public static function workspace() {
  global $wpdb;
  $patients=$wpdb->get_results('SELECT id,name,age,created_at'.(self::clinical()?',notes':'').' FROM '.NGC_Store::table('patients').' ORDER BY id DESC',ARRAY_A);
  return rest_ensure_response(array('patients'=>$patients,'appointments'=>$wpdb->get_results('SELECT id,patient_id,doctor_id,service_id,starts_at,status FROM '.NGC_Store::table('appointments').' ORDER BY starts_at',ARRAY_A),'log'=>$wpdb->get_results('SELECT message,actor,created_at FROM '.NGC_Store::table('log').' ORDER BY id DESC LIMIT 60',ARRAY_A),'canReadNotes'=>self::clinical(),'doctors'=>NGC_Store::doctors(),'services'=>NGC_Store::services(),'today'=>current_datetime()->format('Y-m-d'),'timezone'=>wp_timezone_string()));
 }
 public static function book($r) {
  global $wpdb;
  $date=sanitize_text_field($r->get_param('date')?:''); $time=sanitize_text_field($r->get_param('time')?:''); $doctor=absint($r->get_param('doctor')); $service=absint($r->get_param('service')); $name=sanitize_text_field($r->get_param('name')?:'');
  if($r->get_param('synthetic')!==true) return self::error('synthetic','Confirm that this is fictional data.');
  if(strlen($name)<2 || strlen($name)>120) return self::error('name','Use a fictional name between 2 and 120 bytes.');
  if(!NGC_Store::allowed_date($date) || !in_array($time,NGC_Store::slots(),true) || !in_array($doctor,array(1,2,3),true) || !in_array($service,array(1,2),true)) return self::error('invalid','Choose a valid doctor, service, date and time.');
  $when=new DateTimeImmutable($date.' '.$time,wp_timezone()); if($when<=current_datetime()) return self::error('past','That time has passed. Choose another slot.');
  $rate='ngc_rate_'.hash_hmac('sha256',isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'unknown',wp_salt('nonce')); $count=(int)get_transient($rate);
  if(!self::staff() && $count>=12) return self::error('rate','Demo limit reached. Try again in 10 minutes.',429);
  // Claim the unique slot before inserting a patient. The unique index arbitrates races.
  $ok=$wpdb->insert(NGC_Store::table('appointments'),array('patient_id'=>0,'doctor_id'=>$doctor,'service_id'=>$service,'starts_at'=>"$date $time:00",'status'=>'confirmed','slot_key'=>"$doctor|$date|$time",'created_at'=>current_time('mysql')));
  if(!$ok) return self::error('conflict','That slot is no longer available. Please choose another.',409);
  $aid=$wpdb->insert_id;
  if(!$wpdb->insert(NGC_Store::table('patients'),array('name'=>$name,'age'=>30,'notes'=>'Synthetic booking. No clinical history.','created_at'=>current_time('mysql')))) { $wpdb->delete(NGC_Store::table('appointments'),array('id'=>$aid)); return self::error('storage','The booking could not be saved.',500); }
  $pid=$wpdb->insert_id;
  if(false===$wpdb->update(NGC_Store::table('appointments'),array('patient_id'=>$pid),array('id'=>$aid))) { $wpdb->delete(NGC_Store::table('appointments'),array('id'=>$aid)); $wpdb->delete(NGC_Store::table('patients'),array('id'=>$pid)); return self::error('storage','The booking could not be saved.',500); }
  set_transient($rate,$count+1,600); NGC_Store::log('Demo booking #'.$aid.' confirmed. Notification simulated; no email sent.');
  return new WP_REST_Response(array('id'=>$aid,'status'=>'confirmed','date'=>$date,'time'=>$time,'simulated'=>true),201);
 }
 public static function status($r) {
  global $wpdb; $id=absint($r['id']); $next=sanitize_key($r->get_param('status')); $a=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.NGC_Store::table('appointments').' WHERE id=%d',$id),ARRAY_A);
  if(!$a) return self::error('missing','Appointment not found.',404);
  $allowed=array('confirmed'=>array('checked_in','cancelled'),'checked_in'=>array('completed','cancelled'),'completed'=>array(),'cancelled'=>array());
  if(!in_array($next,$allowed[$a['status']]??array(),true)) return self::error('transition','That status change is not allowed.',409);
  // Conditional write prevents two staff tabs from applying stale transitions.
  $changes=array('status'=>$next); if($next==='cancelled') $changes['slot_key']=null;
  $updated=$wpdb->update(NGC_Store::table('appointments'),$changes,array('id'=>$id,'status'=>$a['status'])); if(!$updated) return self::error('conflict','Appointment changed. Refresh and try again.',409);
  NGC_Store::log('Demo appointment #'.$id.' changed to '.$next.'. Notification simulated; no email sent.'); return array('id'=>$id,'status'=>$next);
 }
 public static function notes($r) {
  global $wpdb; $id=absint($r['id']); $note=sanitize_textarea_field($r->get_param('notes')?:''); if(strlen($note)>4000) return self::error('length','Notes must be under 4,000 bytes.');
  if(!$wpdb->get_var($wpdb->prepare('SELECT id FROM '.NGC_Store::table('patients').' WHERE id=%d',$id))) return self::error('missing','Patient not found.',404);
  if(false===$wpdb->update(NGC_Store::table('patients'),array('notes'=>$note),array('id'=>$id))) return self::error('storage','Could not save the note.',500);
  NGC_Store::log('Synthetic notes updated for record #'.$id.'.'); return array('saved'=>true);
 }
}
