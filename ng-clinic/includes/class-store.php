<?php
if (!defined('ABSPATH')) { exit; }
final class NGC_Store {
 public static function table($name) { global $wpdb; return $wpdb->prefix . 'ngc_' . $name; }
 public static function doctors() { return array(array('id'=>1,'name'=>'Dr. Maya Salem','ar'=>'د. مايا سالم','specialty'=>'Family medicine','specialtyAr'=>'طب الأسرة','initials'=>'MS','color'=>'teal'),array('id'=>2,'name'=>'Dr. Adam Noor','ar'=>'د. آدم نور','specialty'=>'Dermatology','specialtyAr'=>'الأمراض الجلدية','initials'=>'AN','color'=>'blue'),array('id'=>3,'name'=>'Dr. Leila Farid','ar'=>'د. ليلى فريد','specialty'=>'Internal medicine','specialtyAr'=>'الباطنة','initials'=>'LF','color'=>'purple')); }
 public static function services() { return array(array('id'=>1,'name'=>'Consultation','ar'=>'كشف أولي','minutes'=>30,'fee'=>450),array('id'=>2,'name'=>'Follow-up','ar'=>'متابعة','minutes'=>30,'fee'=>250)); }
 public static function slots() { return array('09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30'); }
 public static function activate() {
  global $wpdb;
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  $c=$wpdb->get_charset_collate(); $p=self::table('patients'); $a=self::table('appointments'); $l=self::table('log');
  dbDelta("CREATE TABLE $p (id bigint(20) unsigned NOT NULL AUTO_INCREMENT, name varchar(120) NOT NULL, age smallint unsigned NOT NULL DEFAULT 30, notes text NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id)) $c;");
  dbDelta("CREATE TABLE $a (id bigint(20) unsigned NOT NULL AUTO_INCREMENT, patient_id bigint(20) unsigned NOT NULL, doctor_id smallint unsigned NOT NULL, service_id smallint unsigned NOT NULL, starts_at datetime NOT NULL, status varchar(24) NOT NULL DEFAULT 'confirmed', slot_key varchar(80) DEFAULT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY slot_key (slot_key), KEY starts_at (starts_at), KEY patient_id (patient_id)) $c;");
  dbDelta("CREATE TABLE $l (id bigint(20) unsigned NOT NULL AUTO_INCREMENT, message varchar(255) NOT NULL, actor bigint(20) unsigned NOT NULL DEFAULT 0, created_at datetime NOT NULL, PRIMARY KEY  (id)) $c;");
  add_role('ngc_receptionist','Clinic receptionist (demo)',array('read'=>true,'ngc_manage'=>true));
  add_role('ngc_clinician','Clinic clinician (demo)',array('read'=>true,'ngc_manage'=>true,'ngc_records'=>true));
  $admin=get_role('administrator'); if($admin) { $admin->add_cap('ngc_manage'); $admin->add_cap('ngc_records'); }
  if (!get_option('ngc_seeded')) { self::seed(); update_option('ngc_seeded',1,false); }
 }
 public static function log($message) { global $wpdb; $wpdb->insert(self::table('log'),array('message'=>$message,'actor'=>get_current_user_id(),'created_at'=>current_time('mysql')),array('%s','%d','%s')); }
 public static function seed() {
  global $wpdb; $names=array('Alex Demo','Nour Sample','Sam Example','Lina Demo','Omar Sample','Dana Example','Zain Demo','Rana Sample','Tala Example','Amir Demo','Jana Sample','Eli Example');
  foreach($names as $i=>$name) {
   $wpdb->insert(self::table('patients'),array('name'=>$name,'age'=>22+$i*3,'notes'=>'Synthetic record for interface demonstration. No clinical history.','created_at'=>current_time('mysql'))); $pid=$wpdb->insert_id;
   $date=current_datetime()->modify(($i<8?'0':'+1').' day')->format('Y-m-d'); $time=self::slots()[$i<8?$i:$i-8]; $doc=($i%3)+1; $status=$i<2?'completed':($i===2?'checked_in':'confirmed');
   $wpdb->insert(self::table('appointments'),array('patient_id'=>$pid,'doctor_id'=>$doc,'service_id'=>($i%2)+1,'starts_at'=>"$date $time:00",'status'=>$status,'slot_key'=>"$doc|$date|$time",'created_at'=>current_time('mysql')));
  }
  self::log('Synthetic workspace created. Notifications are logged, never sent.');
 }
 public static function date_valid($date) { $d=DateTimeImmutable::createFromFormat('!Y-m-d',$date,wp_timezone()); return $d && $d->format('Y-m-d')===$date; }
 public static function allowed_date($date) { $today=current_datetime()->format('Y-m-d'); return self::date_valid($date) && $date >= $today && $date <= current_datetime()->modify('+60 days')->format('Y-m-d'); }
 public static function public_catalog($date) {
  global $wpdb; $busy=$wpdb->get_results($wpdb->prepare('SELECT doctor_id, starts_at FROM '.self::table('appointments')." WHERE starts_at >= %s AND starts_at < %s AND status != 'cancelled'",$date.' 00:00:00',$date.' 23:59:59'),ARRAY_A);
  $available=array(); foreach(self::doctors() as $d) { $available[$d['id']]=array_values(array_filter(self::slots(),function($time) use($busy,$date,$d) { if(strtotime($date.' '.$time.':00')===false) return false; $when=new DateTimeImmutable($date.' '.$time,wp_timezone()); if($when<=current_datetime()) return false; foreach($busy as $row) { if((int)$row['doctor_id']===$d['id'] && substr($row['starts_at'],11,5)===$time) return false; } return true; })); }
  return array('doctors'=>self::doctors(),'services'=>self::services(),'available'=>$available,'today'=>current_datetime()->format('Y-m-d'),'date'=>$date,'maxDate'=>current_datetime()->modify('+60 days')->format('Y-m-d'),'timezone'=>wp_timezone_string());
 }
}
