<?php 

// get an imgix url

function getImgix($igfile,$igwidth){
	// get a signed image
	$igbasename = getenv('IMGIXSOURCE').'.imgix.net';
	$igbuilder = new Imgix\UrlBuilder($igbasename);
	$igbuilder->setUseHttps(true);
	$igbuilder->setSignKey(getenv('IMGIXSIGN'));
	$igparams = array("w" => $igwidth, "fit" => "max");
	$igurl = $igbuilder->createURL($igfile, $igparams);
	// return
	return($igurl);
};

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