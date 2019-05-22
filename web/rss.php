<?php

// rss

$dotgne_list_perpage = 50;

require('autoload.php');
date_default_timezone_set('Europe/London');
session_start();

// connect db
$con = pg_connect(getenv('DATABASE_URL'));

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

error_log('permission view level is '.$dotgne_view_level);

// fetch images

$pq1 = 'SELECT * FROM dotgne.photos WHERE "iuser" = '.pg_escape_literal($dotgne_acc).'
ORDER BY "iid" DESC
LIMIT '.$dotgne_list_perpage;
$rs1 = pg_query($con, $pq1);

// setup rss

$rssfeed = '<?xml version="1.0" encoding="ISO-8859-1"?>';
$rssfeed .= '<rss version="2.0">';
$rssfeed .= '<channel>';
$rssfeed .= '<title>My RSS feed</title>';
$rssfeed .= '<link>https://photos.tomroyal.com/rss/'.$dotgne_acc.'/</link>';
$rssfeed .= '<description>RSS of photos</description>';
$rssfeed .= '<language>en-gb</language>';
    
// loop

while($dotgne_lister_pic = pg_fetch_array($rs1)){
  // anon item
  $rssfeed .= '<item>';
  $rssfeed .= '<title>' . $dotgne_lister_pic['iid'] . '</title>';
  $rssfeed .= '<description>Image ID ' . $dotgne_lister_pic['iid'] . '</description>';
  $rssfeed .= '<link>https://photos.tomroyal.com/user/'.$dotgne_acc.'/'.$dotgne_lister_pic['iid'].'/</link>';
  $rssfeed .= '<pubDate>' . date("D, d M Y H:i:s O", strtotime($dotgne_lister_pic['datetaken'])) . '</pubDate>';
  $rssfeed .= '</item>';
}  

// tail rss
$rssfeed .= '</channel>';
$rssfeed .= '</rss>'; 
echo $rssfeed;

?>