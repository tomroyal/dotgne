<?php

// upload page for dotgne

require('autoload.php');
date_default_timezone_set('Europe/London');
session_start();

// connect db
$con = pg_connect(getenv('DATABASE_URL'));

// common funcs
include('./inc/s3.php');
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

// error_log('logged in '.$dotgne_logged_in.' user '.$viewer_acc);

// if here, we're logged in

use EddTurtle\DirectUpload\Signature;

$upload = new Signature(
			    getenv('AWS_ACCESS_KEY_ID'),
			    getenv('AWS_SECRET_ACCESS_KEY'),
			    getenv('AWS_BUCKET'),
			    "eu-west-1"
					);

$remote_name = 	$viewer_acc.'_'.md5('dotgne'.time());

// create a security token based on last image uploaded
// TODO - change to timestamp strtotime of last photo, not known to viewer!
$pq1 = 'SELECT * FROM dotgne.photos WHERE "iuser" = '.pg_escape_string($viewer_acc).' ORDER BY "iid" DESC LIMIT 1';
$rs1 = pg_query($con, $pq1);
$user_last_pic = pg_fetch_array($rs1);
$utoken = md5('dotgne'.strtotime($user_last_pic['datetaken']).$viewer_acc);

include('./html/top.php');	
start_skel_row_12('m50top');	
					
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
<form id="uploadform" action="<?php echo $upload->getFormUrl(); ?>"
      method="POST"
      enctype="multipart/form-data"
      class="direct-upload">

	<?php echo $upload->getFormInputsAsHtml(); ?>
	<h4>Upload a Photo</h4>
	<p>Use this form to add a new photo to your account.</p>
	<label for="file-upload" class="button upbtn1">
	    Choose Image
	</label>
	<input id="file-upload" class="hiddentext" type="file" name="file"/>
	<div class="progress-bar-area"></div>

</form>
<div id="result">
  <!-- output here -->
</div>					
<!-- Start of the JavaScript -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<!-- Load the FileUpload Plugin (more info @ https://github.com/blueimp/jQuery-File-Upload) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/blueimp-file-upload/9.14.1/js/jquery.fileupload.min.js"></script>			

<script>
function urldecode(str) {
	return decodeURIComponent((str+'').replace(/\+/g, '%20'));
}
$(document).ready(function () {
		// Assigned to variable for later use.
		var form = $('.direct-upload');
		var filesUploaded = [];
		// Place any uploads within the descending folders
		// so ['test1', 'test2'] would become /test1/test2/filename
		var folders = ['<?=$viewer_acc;?>'];
		form.fileupload({
				url: form.attr('action'),
				type: form.attr('method'),
				datatype: 'xml',
				add: function (event, data) {
						// Show warning message if your leaving the page during an upload.
						window.onbeforeunload = function () {
								return 'You have unsaved changes.';
						};
						// Set filename
						var file = data.files[0];
						var filename = '<?echo($remote_name.'.jpg');?>';
						form.find('input[name="Content-Type"]').val(file.type);
						if (file.type != 'image/jpeg'){
							$('#result').html('<p>Only JPEG images can be uploaded. please try again.');
						}
						else if (file.size > 10240000){
							$('#result').html('<p>Images must be 10MB or smaller. please try again.');
						}
						else {
							// upload
							$('#result').empty();
							$('#file-upload').hide();
							$('.upbtn1').hide();
							$('.backbtn1').hide();
							form.find('input[name="key"]').val((folders.length ? folders.join('/') + '/' : '') + filename);
							// Actually submit to form to S3.
							data.submit();
							// Show the progress bar
							var bar = $('<div class="progress" data-mod="'+file.size+'"><div class="bar"></div></div>');
							$('.progress-bar-area').append(bar);
							bar.slideDown('fast');
						}
				},
				progress: function (e, data) {
						// Update progress
						var percent = Math.round((data.loaded / data.total) * 100);
						$('.progress[data-mod="'+data.files[0].size+'"] .bar').css('width', percent + '%').html(percent+'%');
				},
				fail: function (e, data) {
						// Remove the 'unsaved changes' message.
						window.onbeforeunload = null;
						$('.progress[data-mod="'+data.files[0].size+'"] .bar').css('width', '100%').addClass('red').html('');
				},
				done: function (event, data) {
						window.onbeforeunload = null;
						// Upload Complete
						var original = data.files[0];
						var s3Result = data.result.documentElement.children;
						var up_name = original.name;
						var up_s3name = s3Result[2].innerHTML;
						var up_size = original.size;
						var up_s3url = s3Result[0].innerHTML.replace("%2F", "/");
						$('#uploadform').remove();
						$('#result').append('<p class="loaderimg"><img src="/img/loading.gif" alt="image loading" />');
						$.ajax({ url: './procupload.php',
							 data: {'f':up_s3url,'t':'<?echo($utoken);?>','u':'<?echo($viewer_acc);?>'},
							 type: 'post',
							 success: function(output) {
									if (output == 'invalid'){
										$('#result').html('<p>You cannot upload any more images.');
									}
									else {
										$('.uploadcount').remove();
											$('#result').append('<p>Image uploaded - you can now add a title and description.');
											// $('#result').append('<p class="loaderimg"><img src="/img/loading.gif" alt="image loading" />');
											$('#result').append('<p>'+urldecode(output));
											$('.fwpic').on('load', function(){
												$('.loaderimg').hide();
											});
											<?php
											
											$writeout = '<form action="./edit/" accept-charset="UTF-8" method="post"><input type="hidden" name="s" value="1"><input type="hidden" name="f" value="'.$remote_name.'.jpg"><p>';
											$writeout .= '<p><label for="title" >Title</label><p><input type="text" class="u-full-width" name="title" id="title" maxlength="200">';
											$writeout .= '<p><label for="desc" >Description</label><textarea class="u-full-width" name="desc" id="desc" maxlength="1000"></textarea>';
											$writeout .= '<p><label for="privacy" >Visible To</label><p><select name="privacy" class="u-full-width"><option value="0">Anyone</option><option value="1">Friends and Family</option><option value="2">Family Only</option><option value="3">Only Me</option></select>';

											// wrap up form
											$writeout = $writeout.'<p><input type="submit" value="Save"></form>';
											?>
											$('#result').append('<?php echo($writeout); ?>');
									}
												}
								});
				}
		});
});
</script>

<?php



end_skel_row_12();

start_skel_row_12('m50top');
echo('<p><a href="'.getenv('BASE_URL').'user/'.$viewer_acc.'/1/">Your Photos</a></p>');
end_skel_row_12();

include('./html/btm.php');

?>
