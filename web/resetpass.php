<?php 

// signup and password reset for dotgne
// receives f = s or r via GET

require('autoload.php');
date_default_timezone_set('Europe/London');

// connect db
$con = pg_connect(getenv('DATABASE_URL'));

$func = $_REQUEST['f']; // s or r
$token = $_REQUEST['t'];
$stage = $_REQUEST['s'];
$show_form = 1; // unless we say otherwise
$show_message = '';

// basic params check
if ((($func != 's') && ($func != 'r')) || ($token == '')){
  // invalid params
  error_log('params error');
  header('Location: '.getenv('BASE_URL'));  
};

// token check
$pq1 = 'SELECT * FROM dotgne.users  
        WHERE "reset" = '.pg_escape_literal($token);
$rs1 = pg_query($con, $pq1);
if (pg_num_rows($rs1) != 1){
  // token fail
  error_log('token error');
  header('Location: '.getenv('BASE_URL')); 
}


if ($stage == '1'){
    // PROCESS DATA
    // do checks
    if ($_POST['p1'] != $_POST['p']){
      // password mismatch
      $show_message .= 'You must enter the same password into both boxes. ';
    }
    if (($func == 's') && ($_POST['n'] == '')){
      $show_message .= 'You must enter a name for your account. ';
    }
    if ($show_message == ''){
      // no errors!
      // hash password properly
      $newpw = password_hash($_POST['p'],PASSWORD_DEFAULT);
      
      $pq1 = 'UPDATE dotgne.users 
      SET "password" = '.pg_escape_literal($newpw).',
      "reset" = NULL';
      if ($func == 's'){
        // also set name
        $pq1 .= ', "uname" = '.pg_escape_literal($_POST['n']);
      }
      $pq1 .= ' WHERE "reset" = '.pg_escape_literal($token);
      $rs1 = pg_query($con, $pq1);
      if (pg_affected_rows($rs1) != 1){
        // err TODO
        error_log('err on pass set/reset');
      }
      else {
        // success!
        $show_form = 0;
        include('./html/top.php');
        include('./inc/skeleton_parts.php');
        start_skel_row_12('m50top');
        echo('<h5>Thank You</h5>');
        echo('<p>You are all set, and you can now <a href="'.getenv('BASE_URL').'login/">log in</a></p>');
        end_skel_row_12();
        include('./html/btm.php');
      }  
    };

}  

if ($show_form == 1){
  // show form for reset or set
  include('./html/top.php');
  include('./inc/skeleton_parts.php');
  start_skel_row_12('m50top');
  
  echo('<form method="post">');
  echo('<input type="hidden" name="s" value="1">');
  echo('<input type="hidden" name="f" value="'.$func.'">');
  echo('<input type="hidden" name="t" value="'.$token.'">');
  
  if ($func == 'r'){
    // password reset
    	echo('<h5>Reset Your Password</h5>');
      echo('<p>Please enter and confirm your new password below</p>');
      $btn_text = 'Reset Password';
  }
  else {
    // signup
    echo('<h5>Welcome!</h5>');
    // TODO something about invite status here
    echo('<p>Please enter your name, then enter and confirm your new password below</p>');
    echo('<label for="n">Your Name</label>');
    echo('<p><input class="u-full-width" type="text" name="n" id="n" required></p>');
    $btn_text = 'Set Password';
  };
  if ($show_message != ''){
		// something's been done and we have a message to show
		echo('<p class="message">'.$show_message.'</p>');
	};
  
  echo('<label for="p">Password</label>');
  echo('<p><input class="u-full-width" type="password" name="p" id="p" required></p>');
  echo('<label for="p1">Confirm Password</label>');
  echo('<p><input class="u-full-width" type="password" name="p1" id="p1" required></p>');
  echo('<p><input class="button-primary" type="submit" value="'.$btn_text.'"></p></form>');
  
  
  end_skel_row_12();
  include('./html/btm.php');  
  
}





















?>