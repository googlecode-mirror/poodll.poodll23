<?php
require_once("../../config.php");

$recorder = optional_param('recorder', "", PARAM_TEXT);
$filename = optional_param('filename', "", PARAM_TEXT);


//if receiving a file write it to temp
if(isset($GLOBALS["HTTP_RAW_POST_DATA"])) {
	$filename = date("Y-m-d_H_i_s", time())."_".rand(100000,900000).".jpg";
	file_put_contents($CFG->dataroot . '/temp/download/' . $filename ,$GLOBALS["HTTP_RAW_POST_DATA"] );
	//tell our widget what the filename we made up is 
	echo $filename; 

}elseif(isset($_FILES["newfile"])){
	$filename = $_FILES["newfile"]['name']; //'nname.mp3' ;//$_POST["filename"];
	$newfilepath = $CFG->dataroot . '/temp/download/' . $filename;
	//file_put_contents($CFG->dataroot . '/temp/download/' . $filename ,$_FILES["newfile"]['tmpname']);
	move_uploaded_file($_FILES["newfile"]["tmp_name"],$newfilepath );
	
	//this is so hacky, I feel like having a shower even typing this, .. if only it wasn't open source ..
	//basically we cant control the recorder behaviour well enough to behave like the other PoodLL widgets
	//unlike the ajax like uploads of poodll recorders, the mp3recorder forwards page on to the POST page, 
	//so we need to return something or the screen will be blank
	//we just return a page containing javascript that pushes the next button in the parent frame
		?>
		<html>
			<head>
				<script type="text/javascript">
					function load()
					{
						var ffield = parent.document.getElementById('filename');
						ffield.value = '<?php echo $filename; ?>';
						parent.document.getElementsByClassName('fp-login-submit')[0].click();
					}
				</script>
			</head>

			<body onload="load()" />
		</html>
		<?php

//if sending a file force download
}else{
	
	
	$fullPath=$CFG->dataroot . '/temp/download/' . $filename;
	if ($fd = fopen ($fullPath, "r")) {
    $fsize = filesize($fullPath);
    $path_parts = pathinfo($fullPath);
    $ext = strtolower($path_parts["extension"]);
    switch ($ext) {
        case "jpg":
        header("Content-type: image/jpeg"); // add here more headers for diff. extensions
        header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\""); // use 'attachment' to force a download
        break;
        
        case "mp3":
        header("Content-type: audio/mpeg3"); // add here more headers for diff. extensions
        header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\""); // use 'attachment' to force a download
        break;
        
        default;
        header("Content-type: application/octet-stream");
        header("Content-Disposition: filename=\"".$path_parts["basename"]."\"");
    }
    header("Content-length: $fsize");
    header("Cache-control: private"); //use this to open files directly
    while(!feof($fd)) {
        $buffer = fread($fd, 2048);
        echo $buffer;
    }
}
fclose ($fd);
exit;
}

?>