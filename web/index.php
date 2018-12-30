<?php

require('autoload.php');
date_default_timezone_set('Europe/London');
session_start();

// connect db
$con = pg_connect(getenv('DATABASE_URL'));

// use s3
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
$s3 = S3Client::factory([
  'version' => '2006-03-01',
  'region' => 'eu-west-1',
  'credentials' => [
      'key'    => getenv('AWS_ACCESS_KEY_ID'),
      'secret' => getenv('AWS_SECRET_ACCESS_KEY')
  ]
]);

include('./inc/common_funcs.php');

// what user account are we viewing? Default to 0
$dotgne_acc = 0;
if ($_GET["u"] != ""){
  $pq1 = 'SELECT * FROM dotgne.users WHERE "id" = '.pg_escape_literal($_GET["u"]);
  $rs1 = pg_query($con, $pq1);
  if (pg_num_rows($rs1) == 1){
    // valid user passed, use that
    $dotgne_acc = $_GET["u"];
  };
};

// now get details of that acc
$pq1 = 'SELECT * FROM dotgne.users WHERE "id" = '.pg_escape_literal($dotgne_acc);
$rs1 = pg_query($con, $pq1);
$dotgne_acc_details = pg_fetch_array($rs1);

// error_log('viewing account '.$dotgne_acc.' email '.$dotgne_acc_details['email']);

// check login status
$dotgne_logged_in_check = do_login_check();
$dotgne_logged_in = $dotgne_logged_in_check[0];
$viewer_acc = $dotgne_logged_in_check[1];
// error_log('login status '.$dotgne_logged_in.' viewer is user '.$viewer_acc);

// check permission level
// default to public only
$dotgne_view_level = 0;
if (($dotgne_logged_in == 1) && ($viewer_acc == $dotgne_acc)){
  // viewing own photos
  $dotgne_view_level = 3; // view all
}
else if ($dotgne_logged_in == 1){
  // different user logged in
  // check their perm level and alter if required
  $pq1 = 'SELECT * FROM dotgne.permissions 
  WHERE "grantfrom" = '.pg_escape_string($dotgne_acc).'
  AND "grantto" = '.pg_escape_string($viewer_acc);
  $rs1 = pg_query($con, $pq1);
  if (pg_num_rows($rs1) == 1){
    // there is a permission granted
    $permission_details = pg_fetch_array($rs1);
    $dotgne_view_level = $permission_details['viewlevel'];
    // error_log('view level granted is '.$dotgne_view_level);
  };
}
// error_log('permission view level is '.$dotgne_view_level);

// actually do display :)

include('./html/top.php');
include('./inc/skeleton_parts.php');
include('./inc/get_imgix.php');
include('./inc/output_funcs.php');

// current view page in list
$view_page = $_GET['p'];
if ($view_page == ''){
  $view_page = 1;
};

if ($_GET["i"] != ''){
  // viewing a single image
  // TODO single view
  $pq1 = 'SELECT * FROM dotgne.photos WHERE "iuser" = '.pg_escape_literal($dotgne_acc).'
  AND "iid" = '.pg_escape_string($_GET["i"]).'
  AND "privacy" <= '.pg_escape_literal($dotgne_view_level);
  $rs1 = pg_query($con, $pq1);
  if (pg_num_rows($rs1) == 1){
    output_single_pic($rs1);
    
    if (($dotgne_logged_in == 1) && ($viewer_acc == $dotgne_acc)){
      // own image, show edit link
      echo('<p><a href="'.getenv('BASE_URL').'edit/'.$_GET["i"].'/">Edit</a></p>');
    };
    
    $dotgne_return_url = getenv('BASE_URL').'user/'.$dotgne_acc.'/'.$view_page.'/';    
    echo('<p><a href="'.$dotgne_return_url.'">Back</a></p>');
  }
  else {
    // error, probably permissions-based
  }
}
else {
  // viewing the index list
  start_skel_row_12('m50top');
  echo('<h2>Photos by '.$dotgne_acc_details['uname'].'</p>');
  end_skel_row_12();
  // draw in main menu
  output_menu();
  // calculate pagination and offsets
  $dotgne_list_perpage = 10;
  $dotgne_list_offset = ($view_page - 1)*$dotgne_list_perpage;
  // build query
  $pq1 = 'SELECT * FROM dotgne.photos WHERE "iuser" = '.pg_escape_literal($dotgne_acc).'
  AND "privacy" <= '.pg_escape_literal($dotgne_view_level).'
  ORDER BY "iid" DESC
  LIMIT '.$dotgne_list_perpage.'
  OFFSET '.$dotgne_list_offset;
  $rs1 = pg_query($con, $pq1);
  
  // list out
  output_pic_list($rs1,2,$view_page); // dbset, columns, viewpage to return to
  // pagination
  output_pagination(); 
}

include('./html/btm.php');

?>
