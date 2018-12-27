<?php 

// account privacy / permissions page for dotgne

require('autoload.php');
date_default_timezone_set('Europe/London');
session_start();

// connect db
$con = pg_connect(getenv('DATABASE_URL'));

// common funcs
include('./inc/common_funcs.php');
include('./inc/skeleton_parts.php');
include('./inc/output_funcs.php');

// are we logged in?
$dotgne_logged_in_check = do_login_check();
$dotgne_logged_in = $dotgne_logged_in_check[0];
$viewer_acc = $dotgne_logged_in_check[1];
if ($dotgne_logged_in == 0){
	// not logged in so cannot upload
	header('Location: '.getenv('BASE_URL').'login/');
};

function permissiontoname($permission){
	// convert numeric permission to name
	if ($permission == 1){
		$show_level = 'a friend';
	} 
	else if ($permission == 2){
		$show_level = 'family';
	};
	return($show_level);
}
function get_their_name($apermission){
	if ($apermission['uname'] === NULL){
		return($apermission['email']);
	}
	else {
		return($apermission['uname'].' ('.$apermission['email'].')');
	};
}

// ok, we're logged in
$list_perms = 1; // if left at 1, interface with list to be shown
$show_message = ''; // if a message needs showing, stick it here

if ($_REQUEST['s'] == 'add'){
    // add a new person - gt is the grantto acc, np is the new permission
    // TODO
		if (filter_var($_POST['gt'], FILTER_VALIDATE_EMAIL)){
			  if (($_POST['np'] > 0) && ($_POST['np'] < 3)){
					// ok, good to use this
					// does account exist?
					$pq1 = 'SELECT * FROM dotgne.users  
									WHERE "email" = '.pg_escape_literal(strtolower($_POST['gt']));
					$rs1 = pg_query($con, $pq1);
					if (pg_num_rows($rs1) == 1){
						// yes, existing user. So this is simple.
						$grantto_acc = pg_fetch_array($rs1);
						$pq1 = 'INSERT INTO dotgne.permissions
						  			("grantfrom","grantto","viewlevel") VALUES
										('.$viewer_acc.','.$grantto_acc['id'].','.pg_escape_string($_POST['np']).')';			
						$rs1 = pg_query($con, $pq1);
						$show_message = 'User '.get_their_name($grantto_acc).' is now set as '.permissiontoname($_POST['np']);
					}
					else {
						// user does not exist, so create them and add perm
						
						// generate a hash for account creation
						$user_hash = md5($_POST['gt'].'dotgne'.$_POST['np'].time());
						// add ghost entry for user - email, no name
						$pq1 = 'INSERT INTO dotgne.users
						  			("email","reset") VALUES
										('.pg_escape_literal(strtolower($_POST['gt'])).','.pg_escape_literal($user_hash).')
										RETURNING id';			
						$rs1 = pg_query($con, $pq1);
						if (pg_num_rows($rs1) == 1){
							// successful add
							$new_user_data = pg_fetch_array($rs1);
							$pq1 = 'INSERT INTO dotgne.permissions
							  			("grantfrom","grantto","viewlevel") VALUES
											('.$viewer_acc.','.$new_user_data['id'].','.pg_escape_string($_POST['np']).')';			
							$rs1 = pg_query($con, $pq1);
							$show_message = strtolower($_POST['gt']).' is not registered. Please send them this link, which will allow them to set a password and view your photos: ';
							$show_message .= ' '.getenv('BASE_URL').'signup/'.$user_hash.'/';
						}
						else {
							error_log('failed to add one new user line');
							// TODO
						};
					};
				}
				else {
					// error_log('invalid perm');
				};
		}
		else {
			// error_log('invalid email');
		};		
}
else if ($_REQUEST['s'] == 'edit'){
    // edit a permission - gt is the grantto acc, np is the new permission
		// get current perms
		$pq1 = 'SELECT * FROM dotgne.permissions  
						inner join dotgne.users on dotgne.users.id = dotgne.permissions.grantto
						WHERE "grantfrom" = '.pg_escape_string($viewer_acc).'
						AND "grantto" = '.pg_escape_string($_POST['gt']);
		$rs1 = pg_query($con, $pq1);
		$current_perm = pg_fetch_array($rs1);
		if (pg_num_rows($rs1) == 1){
			// found that permission so can edit
			if ($_POST['np'] == ''){
				$list_perms = 0; // prevent list output as we're showing a form
				error_log('offer edit on '.$_POST['gt']);
				include('./html/top.php');
				start_skel_row_12('m50top');
				echo('<p>'.get_their_name($current_perm).'\'s current permission level is '.permissiontoname($current_perm['viewlevel']));
				echo('<p>Change their permission level to:');
				echo('<form action="'.getenv('BASE_URL').'privacy/" method="post">');
				echo('<input type="hidden" name="s" value= "edit">');
				echo('<input type="hidden" name="gt" value= "'.$_POST['gt'].'">');
				echo('<p><label for="np" >New Permission Level</label><p><select name="np" class="u-full-width">');
				draw_privacy_options_user($current_perm['viewlevel'],0);
				echo('</select>
				<p><input type="submit" value="Save">');			
				end_skel_row_12();
				include('./html/btm.php');		
			}
			else {
				// do edit
				// first get target acc for messaging
				$pq1 = 'SELECT * FROM dotgne.users  
								WHERE "id" = '.pg_escape_string($_POST['gt']);
				$rs1 = pg_query($con, $pq1);
				$editing_acc = pg_fetch_array($rs1);
				
				if ($_POST['np'] == 0){		
					// delete perm
					$pq1 = 'DELETE FROM dotgne.permissions  
									WHERE "grantfrom" = '.pg_escape_string($viewer_acc).'
									AND "grantto" = '.pg_escape_string($_POST['gt']);
					$rs1 = pg_query($con, $pq1);
					$show_message = 'User '.get_their_name($editing_acc).' is no longer set as a friend or family.';		
				}
				else if ($_POST['np'] < 3){
					$pq1 = 'UPDATE dotgne.permissions  
									SET "viewlevel" = '.pg_escape_string($_POST['np']).'
									WHERE "grantfrom" = '.pg_escape_string($viewer_acc).'
									AND "grantto" = '.pg_escape_string($_POST['gt']);
					$rs1 = pg_query($con, $pq1);	
					$show_message = 'User '.get_their_name($editing_acc).' is now set as '.permissiontoname($_POST['np']);			
				};
			};
		};	
};

// unless told otherwise, show current permissions
if ($list_perms == 1){
	include('./html/top.php');
	start_skel_row_12('m50top');
	echo('<h5>Privacy Settings</h5>');
	
	if ($show_message != ''){
		// something's been done and we have a message to show
		echo('<p class="message">'.$show_message.'</p>');
	};
	
	echo('<p>You have granted access to the following users:</p>');
	echo('<table class="u-full-width">
  <thead>
    <tr>
      <th>User</th>
      <th>Is</th>
      <th>Action</th>
    </tr>
  </thead><tbody>');

	$pq1 = 'SELECT * FROM dotgne.permissions  
					inner join dotgne.users on dotgne.users.id = dotgne.permissions.grantto
					WHERE "grantfrom" = '.pg_escape_string($viewer_acc).'
					order by dotgne.permissions.viewlevel DESC';
  $rs1 = pg_query($con, $pq1);
	while($apermission = pg_fetch_array($rs1)){	
		$show_name = get_their_name($apermission);
		$show_level = permissiontoname($apermission['viewlevel']);			
		echo('<tr>
      <td>'.$show_name.'</td>
      <td>'.$show_level.'</td>
      <td>
			<form action="'.getenv('BASE_URL').'privacy/" class="mb0" method="post">
			<input type="hidden" name="s" value="edit">
			<input type="hidden" name="gt" value="'.$apermission['grantto'].'">
			<input type="submit" value="Edit" class="button" />
			</form>
			</td>
    </tr>');
	};
  echo('</tbody></table>');
	
	// add form
	echo('<h5>Add New</h5>');
	echo('<p>To give access to someone else, please enter their details here.</p>');
	echo('<form action="'.getenv('BASE_URL').'privacy/" class="mb0" method="post">
				<input type="hidden" name="s" value="add">
				<label for="gt">Email Address:</label>
				<input type="email" name="gt" placeholder="email@address.com" class="u-full-width" required />
				<label for="np">Permission Level</label>
				<select name="np" class="u-full-width">');
				draw_privacy_options_user($current_perm['viewlevel'],1);
				echo('</select><p><input type="submit" value="Add">');			
	end_skel_row_12();
	
	start_skel_row_12('m50top');
	echo('<p><a href="'.getenv('BASE_URL').'user/'.$viewer_acc.'/1/">Your Photos</a></p>');
	end_skel_row_12();
	
	include('./html/btm.php');
};	
 
?>