<?php 

// process upload ajax

require('autoload.php');
date_default_timezone_set('Europe/London');

// connect db
$con = pg_connect(getenv('DATABASE_URL'));

$token = $_REQUEST['t']; // user token for confirmation
$u = $_REQUEST['u'];
$f = $_REQUEST['f']; // filename in s3

// verify token

$pq1 = 'SELECT * FROM dotgne.photos WHERE "iuser" = '.pg_escape_string($u).' ORDER BY "iid" DESC LIMIT 1';
$rs1 = pg_query($con, $pq1);
$user_last_pic = pg_fetch_array($rs1);
$check_token = md5('dotgne'.strtotime($user_last_pic['datetaken']).$u);

if ($check_token != $token){
  echo('invalid');
}
else {
  // token check passed
  include('./inc/get_imgix.php');
  include('./inc/output_funcs.php');
  
  // get filename
  $sl_pos = strrpos($f, '/');
  $f_name_only = $sl_pos === false ? $f : substr($f, $sl_pos + 1);
  
  // get exif date 
  if ((getenv('IMGIXSOURCE') == '') || (getenv('IMGIXSOURCE') == 'tbc')){
    // imgix disabled, so don't attempt date fetch
    $taken_datetime = 'failed';
  }
  else {
    $taken_datetime = getImgixDateTime($u.'/'.$f_name_only);
  };
  
  if ($taken_datetime == 'failed'){
    // couldn't read exif date, leave it null
    $pq1 = 'INSERT INTO dotgne.photos (f_url,iuser,privacy) VALUES (\''.pg_escape_string($f_name_only).'\',\''.pg_escape_string($u).'\',\'3\') RETURNING *';
  }
  else {
    // got exif date!
    $pq1 = 'INSERT INTO dotgne.photos (f_url,iuser,privacy,datetaken) VALUES (\''.pg_escape_string($f_name_only).'\',\''.pg_escape_string($u).'\',\'3\',\''.pg_escape_string(date('c',strtotime($taken_datetime))).'\') RETURNING *';
  }
  
  // insert into db
  
  // error_log($pq1);
  $rs1 = pg_query($con, $pq1);
  $dotgne_image = pg_fetch_array($rs1);
  
  // get thumb, return
  $thethumb = draw_pic_fullwidth($dotgne_image); // needs image array
  echo(urlencode($thethumb));
}



?>