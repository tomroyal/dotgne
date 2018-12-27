<?php 
function do_login_check(){
  $dotgne_logged_in = 0;
  $viewer_acc = '';
  global $con;
  $viewer_acc = $_SESSION['dogtne_user'];
  if ($viewer_acc != ''){
    // verify user acc exists
    $pq1 = 'SELECT * FROM dotgne.users WHERE "id" = '.pg_escape_literal($viewer_acc);
    $rs1 = pg_query($con, $pq1);
    if (pg_num_rows($rs1) == 1){
      // yes, valid user logged in
      $dotgne_logged_in = 1;
    };
  };
  return array ($dotgne_logged_in,$viewer_acc);
};


?>