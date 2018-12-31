<?php

function sendEmail($to,$subject,$message){
  global $PMclient;
	try {
		  $sendResult = $PMclient->sendEmail(getenv('POSTMARK_FROM'),
			$to,
			$subject,
			$message);
      return($sendResult['code']);
      error_log('sent email to '.$to);
	} catch (Exception $e) {
		error_log('failed to send email to '.$to);
    retur(0);
	};
};

?>