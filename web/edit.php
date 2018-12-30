<?php

// edit page for dotgne

// todo - allow update q to work based on iid as well as f_url

require('autoload.php');
date_default_timezone_set('Europe/London');
session_start();

// connect db
$con = pg_connect(getenv('DATABASE_URL'));

// common funcs
include('./inc/s3.php');
include('./inc/common_funcs.php');
include('./inc/skeleton_parts.php');

// are we logged in?
$dotgne_logged_in_check = do_login_check();
$dotgne_logged_in = $dotgne_logged_in_check[0];
$viewer_acc = $dotgne_logged_in_check[1];
if ($dotgne_logged_in == 0){
	// not logged in so cannot upload
	header('Location: '.getenv('BASE_URL').'login/');
};

if ($_POST['s'] == 1){
  // save data sent
  $f_url = $_POST['f']; // filename, sent by uploader, ids image
	$f_id = $_POST['i']; // image id, sent by form
  $f_title = $_POST['title'];
  $f_desc = $_POST['desc'];
	$f_priv = $_POST['privacy'];
  
	if (($f_priv < 0) || ($f_priv > 3)){
		$f_priv = 3;
	}
	
  // update the record 
  
  $pq1 = 'UPDATE dotgne.photos 
  SET "title" = '.pg_escape_literal($f_title).',
  "description" = '.pg_escape_literal($f_desc).',
	"privacy" = '.pg_escape_string($f_priv).'
	WHERE "iuser" = '.pg_escape_string($viewer_acc);
	
	if ($f_url != ''){
		// use f_url - from upload
		$pq1 .= ' AND "f_url" = '.pg_escape_literal($f_url);
	}
	else {
		// use iid - from editor
		$pq1 .= ' AND "iid" = '.pg_escape_string($f_id);
	};
	
  $rs1 = pg_query($con, $pq1);
  if (pg_affected_rows($rs1) == 1){
		// TODO - maybe to image?
    header('Location: '.getenv('BASE_URL').'user/'.$viewer_acc.'/1/');
  }
  else {
    // TODO - what? This is a user auth error
  }
  
}
else {
  // offer edit
	
	$image_id = $_REQUEST['i'];
	// TODO - check image, show image, complete form below - with i, not f_url?
	$pq1 = 'SELECT * FROM dotgne.photos WHERE "iuser" = '.pg_escape_literal($viewer_acc).'
  AND "iid" = '.pg_escape_string($image_id);
  $rs1 = pg_query($con, $pq1);
	if (pg_num_rows($rs1) != 1){
		// TODO this user does not own that image so er nope
	}
	else {
		$image_data = pg_fetch_array($rs1);
		include('./html/top.php');	
		start_skel_row_12('m50top');	
		include('./inc/get_imgix.php');
		include('./inc/output_funcs.php');
		echo(draw_pic_fullwidth($image_data));
		
		?>
		<form action="./edit/" accept-charset="UTF-8" method="post">
		<input type="hidden" name="s" value="1">
		<input type="hidden" name="i" value="<?=$image_id;?>"><p>
		<p><label for="title" >Title</label><p><input type="text" class="u-full-width" name="title" id="title" maxlength="200" value="<?=$image_data['title'];?>">
		<p><label for="desc" >Description</label><textarea class="u-full-width" name="desc" id="desc" maxlength="1000"><?=$image_data['description'];?></textarea>
		<p><label for="privacy" >Visible To</label><p><select name="privacy" class="u-full-width">
			<?php
			draw_privacy_options($image_data['privacy']);
			?>
		</select>
		<p><input type="submit" value="Save">
		</form>
		<?php
		end_skel_row_12();
		start_skel_row_12('m50top');
		echo('<p><a href="'.getenv('BASE_URL').'user/'.$viewer_acc.'/1/">Back</a></p>');
		end_skel_row_12();
		include('./html/btm.php');  
	}
	
	
};

?>