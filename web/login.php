<?php

// login page for dotgne

require('autoload.php');
date_default_timezone_set('Europe/London');

if (isset($_SESSION)){
	session_destroy();
};
session_start();

// connect db
$con = pg_connect(getenv('DATABASE_URL'));

// form vars

$stage    = $_POST['s'];
$e        = $_POST['e'];
$p        = $_POST['p'];

function showloginform(){
  // login form
  echo('<form method="post">');
  echo('<label for="e">Email</label>');
  echo('<p><input class="u-full-width" type="email" placeholder="email@address.com" name="e" id="e" required></p>');
  echo('<label for="p">Password</label>');
  echo('<p><input class="u-full-width" type="password" name="p" id="p" required></p>');
  echo('<input type="hidden" name="s" value="1">');
  echo('<p><input class="button-primary" type="submit" value="Log In"></p></form>');
};

include('./html/top.php');
include('./inc/skeleton_parts.php');
start_skel_row_12('m50top');

if ($stage == 1){
  // process login details
  $e = pg_escape_string($_POST['e']);
  // TODO
  $pq = 'SELECT * FROM dotgne.users WHERE "email" = \''.trim(strtolower(pg_escape_string($e))).'\'';
  $rs = pg_query($con,$pq);
  while ($thisaccount = pg_fetch_array($rs)){
    if (password_verify($p,$thisaccount['password'])){
      $loginok = 1;
      break;
    };
  };
  if ($loginok == 1){
    $_SESSION['dogtne_user'] = $thisaccount['id'];
    // error_log('successful login for '.$thisaccount['id']);
    header('Location: '.getenv('BASE_URL').'user/'.$thisaccount['id'].'/1/');
    die;
  }
  else {
    // fail
    echo('<p class="alert">Sorry, please try again.</p>');
    // error_log('failed login');
    showloginform();
  }
}
else {
  // show login form
  echo('<h4>Log In</h4>');
  echo('<p>Please log in with your email address and password.');
  showloginform();
}

end_skel_row_12();
include('./html/btm.php');

?>
