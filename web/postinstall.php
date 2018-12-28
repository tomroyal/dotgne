<?php

// installation script for dot gne

require('autoload.php');
date_default_timezone_set('Europe/London');

// connect db
$con = pg_connect(getenv('DATABASE_URL'));
include('./inc/skeleton_parts.php');

// install stage
$stage = $_POST['s'];
$show_message = ''; // for errors

// is schema set up? if not, stage 1
$pq1 = 'SELECT schema_name FROM information_schema.schemata WHERE schema_name = \'dotgne\'';
$rs1 = pg_query($con,$pq1);
if (pg_num_rows($rs1) != 1){
  $stage = 0;
}
else {
  // is setup already complete? if so, die
  $pq1 = 'SELECT * FROM dotgne.flags WHERE "iuser" = 0 AND "flag" = \'setup_complete_v1\'';
  $rs1 = pg_query($con,$pq1);
  if (pg_num_rows($rs1) != 0){
    error_log('setup already complete, dying');
    die;
  }
}
// check pass match
if (($stage == 1) && ($_POST['upass'] != $_POST['upass2'])){
  // password mismatch
  $show_message .= 'You must enter the same password into both boxes. ';
  $stage = 0;
}

include('./html/top.php');
start_skel_row_12('m50top');

switch ($stage){
  case 0:
    // offer setup
    if ($show_message != ''){
      echo('<p class="message">'.$show_message.'</p>');
    }
    
    echo('<h5>Welcome to dot GNE</h5>');
    echo('<p>This page will set up the database of images and users, and create your account.</p>');
    echo('<form action="postinstall.php" accept-charset="UTF-8" method="post">');
    echo('<input type="hidden" name="s" value="1">');
    echo('<p><label for="uname">Your Name (eg, Jane Smith)</label><p><input type="text" class="u-full-width" name="uname" id="uname" maxlength="200" required>');
    echo('<p><label for="uemail">Your Email Address</label><p><input type="email" class="u-full-width" name="uemail" id="uemail" maxlength="200"  required>');
    echo('<p><label for="upass">Password</label><p><input type="password" class="u-full-width" name="upass" id="upass" required>');
    echo('<p><label for="upass2">Confirm Password</label><p><input type="password" class="u-full-width" name="upass2" id="upass2" required>');
    echo('<p><input type="submit" value="Set It Up!">');
        
    break;
  
  case 1:
    try {
      // set up schema
      $pq1 = 'CREATE SCHEMA dotgne';
      $rs1 = pg_query($con,$pq1); 
      
      // set up tables
      $pq1 = 'CREATE TABLE dotgne.users (
                "id" serial,
                email text NOT NULL,
                password text,
                reset text,
                uname text
              )';
      $rs1 = pg_query($con,$pq1); 
      
      $pq1 = 'CREATE TABLE dotgne.permissions (
                grantfrom integer references dotgne.users(id),
                grantto integer references dotgne.users(id),
                viewlevel integer not null
              )';
      $rs1 = pg_query($con,$pq1);     
      
      $pq1 = 'CREATE TABLE dotgne.photos (
                iid serial,
                iuser integer references dotgne.users(id),
                f_id bigint,
                f_url text,
                title text,
                description text,
                tags text,
                datetaken timestamp without time zone,
                privacy integer
              )';
      $rs1 = pg_query($con,$pq1); 
      
      $pq1 = 'CREATE TABLE dotgne.flags (
                iuser integer references dotgne.users(id),
                flag text,
                state boolean
              )';
      $rs1 = pg_query($con,$pq1);    
    
      // add admin user
      $newpw = password_hash($_POST['upass'],PASSWORD_DEFAULT);
      $pq1 = 'INSERT INTO dotgne.users
              ("id","email","password","uname") VALUES
              (0,'.pg_escape_literal(strtolower($_POST['uemail'])).','.pg_escape_literal($newpw).','.pg_escape_literal(uname).')
              RETURNING id';			
      $rs1 = pg_query($con, $pq1);
      
      // add setup complete flag
      $pq1 = 'INSERT INTO dotgne.flags
              ("iuser","flag","state") VALUES
              (0,\'setup_complete_v1\',TRUE)';			
      $rs1 = pg_query($con, $pq1);

      echo('<p>Installation succeeded. Click <a href="'.getenv('BASE_URL').'login/" class="">here</a> to log in.');
      
    } catch (Exception $e){
      echo('<p>Installation failed. Caught error: '.$e->getMessage());
    }
    
    break;
  
  
} // switch;

end_skel_row_12();
include('./html/btm.php');


?>