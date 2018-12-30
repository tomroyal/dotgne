<?php 

// get an imgix url

function getThumbnail($igfile,$igwidth){
	// get a signed image via Imgix, or fallback to an S3 fetch
	if ((getenv('IMGIXSOURCE') == '') || (getenv('IMGIXSOURCE') == 'tbc')){
    // imgix disabled, get s3
		$igurl = getS3fullresurl($igfile);
  }
	else {
		// imgix thumb
		$igbasename = getenv('IMGIXSOURCE').'.imgix.net';
		$igbuilder = new Imgix\UrlBuilder($igbasename);
		$igbuilder->setUseHttps(true);
		$igbuilder->setSignKey(getenv('IMGIXSIGN'));
		$igparams = array("w" => $igwidth, "fit" => "max");
		$igurl = $igbuilder->createURL($igfile, $igparams);
	};
	// return
	return($igurl);
};

// get S3 image URL
function getS3fullresurl($thefile){
	$s3 = S3Client::factory([
		'version' => '2006-03-01',
		'region' => 'eu-west-1',
		'credentials' => [
				'key'    => getenv('AWS_ACCESS_KEY_ID'),
				'secret' => getenv('AWS_SECRET_ACCESS_KEY')
		]
	]);
	try {
			// fetch presigned url
			$cmd = $s3->getCommand('GetObject', [
				'Bucket' => getenv('AWS_BUCKET'),
				'Key'    => $thefile
			]);
			$request = $s3->createPresignedRequest($cmd, '+10 minutes');
			$presignedUrl = (string) $request->getUri();
			return($presignedUrl);
	}
	catch (S3Exception $e) {
		error_log('s3 error');
		return('failed'); // TODO
	};
};

// get EXIF date via Imgix

function getImgixDateTime($igfile){
	// get json meta url
	$igbasename = getenv('IMGIXSOURCE').'.imgix.net';
	$igbuilder = new Imgix\UrlBuilder($igbasename);
	$igbuilder->setUseHttps(true);
	$igbuilder->setSignKey(getenv('IMGIXSIGN'));
	$igparams = array("fm" => "json");
	$igurl = $igbuilder->createURL($igfile, $igparams);
	// error_log('gidt url '.$igurl);
	// fetch url
	try {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL,$igurl);
		$result=curl_exec($ch);
		curl_close($ch);
		// get datetime
		$imgdata = json_decode($result);
		$img_date = $imgdata->Exif->DateTimeOriginal;
		// error_log('gidt '.$img_date);
		if ($img_date != ''){
			return($img_date);
		}
		else {
			return('failed');
		}
		return($img_date);
	} catch (Exception $e) {
			error_log($e->getMessage());
			return('failed');
	};		
};

?>