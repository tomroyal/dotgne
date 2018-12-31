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

// do we have mail sending ability? If so, prepare it
if ((getenv('POSTMARK_KEY') != '') && (getenv('POSTMARK_KEY') != 'tbc')){
	$can_email = 1;
	use Postmark\PostmarkClient;
	$PMclient = new PostmarkClient(getenv('POSTMARK_KEY'));
};

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
    echo('<p class="message">Sorry, please try again.</p>');
    // error_log('failed login');
    showloginform();
  }
}
else if ($stage == 2){
	// offer pass reset form
	echo('<p>Lost your password? Please enter your email address here and we will send you a reset link</p>');
	echo('<form method="post">');
  echo('<label for="e">Email</label>');
  echo('<p><input class="u-full-width" type="email" placeholder="email@address.com" name="e" id="e" required></p>');
  echo('<input type="hidden" name="s" value="3">');
  echo('<p><input class="button-primary" type="submit" value="Reset Password"></p></form>');
}
else if ($stage == 3){
	// attempt pass reset
	$e = pg_escape_string(trim(strtolower($_POST['e'])));
  $pq = 'SELECT * FROM dotgne.users WHERE "email" = \''.$e.'\'';
  $rs = pg_query($con,$pq);
	if (pg_num_rows($rs) == 1){
		$thisaccount = pg_fetch_array($rs);
		$reset_hash = md5($thisaccount['id'].'dotgnepwr'.time());
		$pq = 'UPDATE dotgne.users SET "reset" = '.pg_escape_literal($reset_hash).' WHERE "email" = \''.$e.'\'';
	  $rs = pg_query($con,$pq);
		include('./inc/send_email.php');
		$message = 'Reset your password here: '.getenv('BASE_URL').'reset/'.$reset_hash.'/';
		$emailresult = sendEmail($e,'Reset your password',$message);
	};
	echo('<p class="message">Thank you. If you have an account, an email has been sent with instructions.</p>');
	
}	
else {
  // show login form
  echo('<h4>Log In</h4>');
  echo('<p>Please log in with your email address and password.');
  showloginform();
	if ($can_email == 1){
		// offer email password reset
		echo('<form method="post">');
		echo('<input type="hidden" name="s" value="2">');
	  echo('<p><input class="button" type="submit" value="Reset Password"></p></form>');
	};
};

end_skel_row_12();
include('./html/btm.php');

?>
