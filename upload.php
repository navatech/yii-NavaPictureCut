<?php

require_once('class/PictureCut.php');

try {

	$pictureCut = PictureCut::createSingleton();
	
	if($pictureCut->upload()){
		print $pictureCut->toJson();
	} else {
		print $pictureCut->exceptionsToJson();
	}

} catch (Exception $e) {
	print $e->getMessage();
}


