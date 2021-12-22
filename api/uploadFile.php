<?php
//header('Access-Control-Allow-Origin: *');

// var_dump($_POST);
// var_dump($_FILES);
// die;

$location = '../assets/images/request-photos';

switch ($_POST['type']) {
	case 'request-photos':
		$location = '../assets/images/request-photos';
		break;
}

$uploadfilename = (isset($_POST['filename']) && !empty($_POST['filename'])) ? $_POST['filename'] : $_FILES['file']['name'];
// die($uploadfilename);

if(move_uploaded_file($_FILES['file']['tmp_name'], $location.'/'. $uploadfilename)){
	echo 'OK';
} else {
	echo 'ERROR:'.$_FILES['file']['error'];
}
