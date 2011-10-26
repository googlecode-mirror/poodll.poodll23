<?php

/**
* internal library of functions and constants for Poodll modules
* accessed directly by poodll flash wdgets on web pages.
* @package mod-poodllpairwork
* @category mod
* @author Justin Hunt
*
*/


/**
* Includes and requires
*/
require_once("../config.php");
require_once('../filter/poodll/poodllinit.php');
//commented just while getting other mods working

//added for moodle 2
require_once($CFG->libdir . '/filelib.php');

	$datatype = optional_param('datatype', "", PARAM_TEXT);    // Type of action/data we are requesting
	$courseid  = optional_param('courseid', 0, PARAM_INT);  // the id of the course 
	$moduleid  = optional_param('moduleid', 0, PARAM_INT);  // the id of the module 
	$hash  = optional_param('hash', "", PARAM_TEXT);  // file or dir hash
	$requestid  = optional_param('requestid', "", PARAM_TEXT);  // file or dir hash
	$paramone  = optional_param('paramone', "", PARAM_TEXT);  // nature of value depends on datatype, maybe path
	$paramtwo  = optional_param('paramtwo', "", PARAM_TEXT);  // nature of value depends on datatype, maybe protocol
	$paramthree  = optional_param('paramthree', "", PARAM_TEXT);  // nature of value depends on datatype, maybe filearea

	switch($datatype){



		case "repodirlist": 
			header("Content-type: text/xml");
			echo "<?xml version=\"1.0\"?>\n";
			$returnxml=fetch_repodirlist($paramone);
			break;	
			
		case "instancedirlist": 
			header("Content-type: text/xml");
			echo "<?xml version=\"1.0\"?>\n";
			$returnxml=fetch_instancedirlist($moduleid, $courseid, $paramone, $paramtwo);
			break;
				
		case "instancedeleteall": 
			header("Content-type: text/xml");
			echo "<?xml version=\"1.0\"?>\n";
			$returnxml=instance_deleteall($moduleid, $courseid, $paramone, $requestid);
			break;
			
		case "instancecopyfile": 
			header("Content-type: text/xml");
			echo "<?xml version=\"1.0\"?>\n";
			$returnxml=instance_copyfilein($moduleid, $courseid, $paramone, $paramtwo,$paramthree, $requestid);
			break;
		
		case "instancedeletefile": 
			header("Content-type: text/xml");
			echo "<?xml version=\"1.0\"?>\n";
			$returnxml=instance_deletefile($hash, $requestid);
			break;
			
		case "instancecreatedir": 
			header("Content-type: text/xml");
			echo "<?xml version=\"1.0\"?>\n";
			$returnxml=instance_createdir($moduleid, $courseid, $paramone, $paramtwo, $requestid);
			break;

		case "instancecopydir": 
			header("Content-type: text/xml");
			echo "<?xml version=\"1.0\"?>\n";
			$returnxml=instance_copydirin($moduleid, $courseid, $paramone, $paramtwo, $paramthree, $requestid);
			break;
			
		default:
			header("Content-type: text/xml");
			echo "<?xml version=\"1.0\"?>\n";
			$returnxml="";
			break;	
		

	}


	echo $returnxml;
	return;




//Fetch a sub directory list for file explorer  
//calls itself recursively, dangerous
function fetch_repodircontents($dir,  $recursive=false){
	$xml_output="";
	$files = scandir($dir);
	if (!empty($files)) {
        foreach ($files as $afile) {
			if ($afile == "." || $afile == "..") {
				continue;
			}
			//here we encode the filename 
			//because the xml breaks otherwise when there arequotes etc.
			$escapedafile =  htmlspecialchars( $afile,ENT_QUOTES);
			if(is_dir($dir."/".$afile)){
				if(!$recursive){
					$xml_output .=  "\t<directory name='" . $escapedafile . "' />\n";
				}else{				
					//recursive
					$xml_output .=  "\t<directory name='" . $escapedafile . "' >\n";
					$xml_output .= fetch_repodircontents($dir."/".$afile,true);	
					$xml_output .=  "\t</directory>";
				}				
			}else{
				$xml_output .=  "\t<file name='" . $escapedafile ."' isleaf='true' "  
				. " filesize='" . filesize($dir . "/" . $afile)  
				. "' created='" . date('d M Y H:i:s', filectime($dir . "/" . $afile)) 
				. "' modified='" . date('d M Y H:i:s', filemtime($dir . "/" . $afile)) 
				. "' type='" .  htmlspecialchars(mime_content_type ($dir . "/" . $afile),ENT_QUOTES)  
				. "'/>\n";
				
				
				
				
				
			}
		}
	}
	return $xml_output;
}



//Fetch a directory list from the repo
function fetch_repodirlist($startpath=''){
	global $CFG;	
	
	
	global $basedir;
    global $usecheckboxes;
    global $id;
    global $USER, $CFG;
	
	//Handle directories
	$fullpath = $CFG->{'dataroot'}  . $startpath;
	
	//open xml to return
	$xml_output = "<directorylist>";
	
	
	
	/* New way which works with php5, but not is_dir : Justin */
	$files = scandir($fullpath);
	if (!empty($files)) {
		$xml_output .= fetch_repodircontents($fullpath,true);
	}
	
	
	
	//close xml to return
	$xml_output .= "</directorylist>";
	
	//Return the data
	return $xml_output;
	
	
}




function fetch_instancedir_contents($thedir, &$thecontext, $recursive=false){
	
	$browser = get_file_browser();
	$xml_output="";
	
	//first process subdirectories (if recursive)
	if(!empty($thedir['subdirs']) && $recursive){
	
		usort($thedir['subdirs'], "cmpDirnames");
		
		 foreach ($thedir['subdirs'] as $subdir) {
			 //this is only necessary of you deleted the dirfile without deleting the subfiles
			 //ie only a dev, not real world situation
			 if(!array_key_exists('dirfile',$subdir)){return;}
			$f = $subdir['dirfile'];
			//$filename =$f->get_filename();
			$filename=poodllBasename($f->get_filepath());
			/*
			if ($filename == "." || $filename == "..") {
				continue;
			}
			*/
			
			
					
			//fetch our info object
			$fileinfo = $browser->get_file_info($thecontext, $f->get_component(),$f->get_filearea(), $f->get_itemid(), $f->get_filepath(), $f->get_filename());
			
			//get the url to the file
				if($fileinfo){
					$urltofile = $fileinfo->get_url();
				}else{
					$urltofile = "accessdenied";
				}
	
			//filehash for any delete/edit manipulations we wish to do
			$hash= $f->get_pathnamehash();	
				
			//output xml for dir (escape for odd quotes that kill xml parser)
			$xml_output .=  "\t<directory name='" . htmlspecialchars($filename,ENT_QUOTES) ."'  url='" . htmlspecialchars($urltofile,ENT_QUOTES) . "' hash='" . $hash . "'>\n";
			$xml_output .= fetch_instancedir_contents($subdir,$thecontext,true);	
			$xml_output .=  "\t</directory>";
		
	
		}
		
	}
	
	//then process files
	$files = $thedir['files'];
	if (!empty($files)) {
		usort($files, "cmpFilenames");
			foreach ($files as $f) {
				$filename =$f->get_filename();
				if ($filename == "." || $filename == "..") {
					continue;
				}
		
				//fetch our info object
				$fileinfo = $browser->get_file_info($thecontext, $f->get_component(),$f->get_filearea(), $f->get_itemid(), $f->get_filepath(), $f->get_filename());

				
				//get the url to the file
				if($fileinfo){
					$urltofile = $fileinfo->get_url();
				}else{
					$urltofile = "accessdenied";
				}
				
				//filehash for any delete/edit manipulations we wish to do
				$hash= $f->get_pathnamehash();
					
				//create the output xml for this file/dir, we escape special characters so as not to break XML parsing
				$xml_output .=  "\t<file name='" . htmlspecialchars($filename,ENT_QUOTES) ."' isleaf='true' url='" . 
						htmlspecialchars($urltofile,ENT_QUOTES)  . "' filesize='" . $f->get_filesize()  
						. "' created='" . date('d M Y H:i:s', $f->get_timecreated())  
						. "' modified='" . date('d M Y H:i:s', $f->get_timemodified()) 
						. "' type='" . $f->get_mimetype() 
						. "' hash='" . $hash . "'/>\n";
		
				
		}
	
	}
	
	return $xml_output;

}

//This will fetch the directory list of all the files
//available in a module instance (ie added from repository)
function fetch_instancedirlist($moduleid, $courseid,  $path, $filearea){
global $CFG, $DB;


	
	//FIlter could submit submission/draft/content/intro as options here
	if($filearea == "") {$filearea ="content";}
	
	//fetch info and ids about the module calling this data
	$course = $DB->get_record('course', array('id'=>$courseid));
	$modinfo = get_fast_modinfo($course);
	$cm = $modinfo->get_cm($moduleid);

	//get filehandling objects
	$browser = get_file_browser();
	$fs = get_file_storage();
	
	//set up xml to return	
	$xml_output = "<directorylist>\n";
	

	//get a handle on the module context
	$thiscontext = get_context_instance(CONTEXT_MODULE,$moduleid);
	$contextid = $thiscontext->id;
	
	//fetch a list of files in this area, and sort them alphabetically
	//how should we handle itemid? 0?
	$itemid = 0;
	$topdir = $fs->get_area_tree($contextid, "mod_" . $cm->modname, $filearea,$itemid);
	
	//when dev/testing set the recursive flag to false if you prefer not to wait for infinite loops.
	$xml_output .= fetch_instancedir_contents($topdir,$thiscontext,true);
	
	//close xml to return
	$xml_output .= "</directorylist>";

	//Return the data
	return $xml_output;

}



function instance_deleteall($moduleid, $courseid, $filearea, $requestid){
	global $CFG, $DB;

	//FIlter could submit submission/draft/content/intro as options here
	if($filearea == "") {$filearea ="content";}
	
	//fetch info and ids about the module calling this data
	$course = $DB->get_record('course', array('id'=>$courseid));
	$modinfo = get_fast_modinfo($course);
	$cm = $modinfo->get_cm($moduleid);
	$component = "mod_" . $cm->modname;
	
	//get a handle on the module context
	$thiscontext = get_context_instance(CONTEXT_MODULE,$moduleid);
	$contextid = $thiscontext->id;

	//get filehandling objects
	$browser = get_file_browser();
	$fs = get_file_storage();
	
	//default is to delete all files, but say for a forum, itemid could distinguish between posts)
	$itemid=0;
	
	//set up xml to return	
	$xml_output = "<result requestid='" . $requestid . "'>\n";
	
	if($fs->delete_area_files($contextid, $component, $filearea, $itemid)){
		$xml_output .= "success";
	}else{
		$xml_output .= "failure";
	}
	
	$xml_output .= "</result>\n";	
	
	//Return the data
	return $xml_output;

}



function instance_deletefile($filehash, $requestid){
	$fs = get_file_storage();
	$f = $fs->get_file_by_hash($filehash);
	
	//set up return object	
	$return=fetchReturnArray(true);
	
	
	//if we don't get a file we can out
	if(!$f){
		$return['success']=false;
		array_push($return['messages'],"no such file/dir to delete." );
	
	}else if($f->is_directory()){
	   $sreturn= instance_deletedircontents($f);
	   $return = mergeReturnArrays($return,$sreturn);
	
	}else{
		if($f->delete()){
			$return['success']=true;	
		}
	
	}
	
	//we process the result for return to browser
	$xml_output=prepareXMLReturn($return, $requestid);		   
	return $xml_output;
}

function instance_deletedircontents($sfdir){
	
	
	//set up return object	
	$return=fetchReturnArray(true);
	
	 $fs = get_file_storage();
	if($sfdir->is_directory()){
		$files = $fs->get_directory_files( $sfdir->get_contextid(), 
										 $sfdir->get_component(),
										 $sfdir->get_filearea(),
										 $sfdir->get_itemid(), 
										 $sfdir->get_filepath(), 
										 true,true);
		
		foreach($files as $singlefile){
			if(!$singlefile->is_directory()){
				if(!$singlefile->delete()){				
					$return['success']=false;
					array_push($return['messages'],"unable to delete" . $singlefile->get_filepath() . " "  . $singlefile->get_filename());
				}
			}else{
				$sreturn = instance_deletedircontents($singlefile);
				$return = mergeReturnArrays($return,$sreturn);
			}
		}
		
		//if we could delete all subfiles and dirs, then we can delete this dir itself.
		$files = $fs->get_directory_files( $sfdir->get_contextid(), 
									 $sfdir->get_component(),
									 $sfdir->get_filearea(),
									 $sfdir->get_itemid(), 
									 $sfdir->get_filepath(), 
									 true,true);
		if(!($files && $files.length >0)){
			$sfdir->delete();
		}	
	}else{
		$return['success'] = false;
		array_push($return['messages'],"unable to delete dir (" . $sfdir->get_filename() . ") because it is not a dir.");
	}
	
	return $return;
}


function instance_createdir($moduleid, $courseid, $filearea, $newdir, $requestid){
	$return = do_createdir($moduleid, $courseid, $filearea, $newdir);
	$xml_return = prepareXMLReturn($return,$requestid);
	return $xml_return;
}

function do_createdir($moduleid, $courseid, $filearea, $newdir){
		global $CFG, $DB;

	//FIlter could submit submission/draft/content/intro as options here
	if($filearea == "") {$filearea ="content";}
	
	//fetch info and ids about the module calling this data
	$course = $DB->get_record('course', array('id'=>$courseid));
	$modinfo = get_fast_modinfo($course);
	$cm = $modinfo->get_cm($moduleid);
	$component = "mod_" . $cm->modname;
	
	//get a handle on the module context
	$thiscontext = get_context_instance(CONTEXT_MODULE,$moduleid);
	$contextid = $thiscontext->id;

	//get filehandling objects
	$browser = get_file_browser();
	$fs = get_file_storage();
	
	//default item id
	$itemid=0;
	
	//set up return object	
	$return=fetchReturnArray(true);
	
	//Must begin and end with slash
	if($newdir != ''){
		if (strpos($newdir, '/') !== 0){
			$newdir= '/' . $newdir;
		}
		if (strrpos($newdir , '/') !== strlen($newdir)-1){
			$newdir= $newdir . '/' ;
		}
	}else{
		$newdir= '/' ;
	}
	
	//check if file already exists, if so can out
	if($fs->file_exists($contextid,$component,$filearea,$itemid,$newdir,".")){
		//set up return object	
		$return['success']=false;
		array_push($return['messages'],$newdir . " :already exists here.");
		
		
		//for some reason this always returns false.	
	}else if($fs->create_directory($contextid, $component, $filearea, $itemid, $newdir)){
		$return['success']=true;
	
	}else{
	   $return['success']=false;
	   array_push($return['messages'],"unable to create dir: " . $newdir );
	
	}
		   
	return $return;	

}

function instance_exists($pathname){
	return file_exists_by_hash($pathname);
}

function do_copyfilein($moduleid, $courseid, $filearea, $filepath,$newpath, $requestid){
	global $CFG, $DB;

	//new return values array
	$return = fetchReturnArray(false);
	
	//FIlter could submit submission/draft/content/intro as options here
	if($filearea == "") {$filearea ="content";}
	
	//fetch info and ids about the module calling this data
	$course = $DB->get_record('course', array('id'=>$courseid));
	$modinfo = get_fast_modinfo($course);
	$cm = $modinfo->get_cm($moduleid);
	$component = "mod_" . $cm->modname;
	
	//get a handle on the module context
	$thiscontext = get_context_instance(CONTEXT_MODULE,$moduleid);
	$contextid = $thiscontext->id;
	
	//get filehandling objects
	$browser = get_file_browser();
	$fs = get_file_storage();
	
	//Make full path to source file
	$filepath = $CFG->{'dataroot'} . $filepath;
	
	//Make full"virtual path" as new path
	if($newpath != ''){
		if (strpos($newpath, '/') !== 0){
			$newpath= '/' . $newpath;
		}
		if (strrpos($newpath , '/') !== strlen($newpath)-1){
			$newpath= $newpath . '/' ;
		}
		//$newpath= '/' . $newpath . '/';
	}else{
		$newpath= '/' ;
	}
	
	//Make filename
	//basename dont work well for multibyte unless locale set so try the explode function(maybe unic dependant though)
	//$filename=basename($filepath);
	$filename = poodllBasename($filepath);
	
	//default item id
	$itemid=0;
	
	//check if file already exists, if so can out
	if($fs->file_exists($contextid,$component,$filearea,$itemid,$newpath,$filename)){
		$return['success'] = false;
		array_push($return['messages'],$filename . " already exists at " . $newpath);
		
		//Return the data
		return $return;
	}
	
	//new filearray
	$newfile = array();
	$newfile['contextid'] = $contextid;
	$newfile['component'] = $component;
	$newfile['filearea'] = $filearea;
	$newfile['itemid'] = $itemid;
	$newfile['filepath'] = $newpath; // I guess change here for subdirs, begin slash, trail slash
	$newfile['sortorder'] = "0";
	$newfile['filename'] = $filename;
	
	
	//set up xml to return	
	$xml_output = "<result requestid='" . $requestid . "'>";
	
	if($fs->create_file_from_pathname($newfile, $filepath)){
	//if(true){
		
		$return['success'] = true;
		
	//	$return['success'] = false;
	//	array_push($return['messages'], $newpath . "  " . $filepath);
		
		return $return;
	}else{
		$return['success'] = false;
		array_push($return['messages'],"Unable to create " . $filename . " at " . $newpath);
		return $return;
	}
	
}


//Copy a single file into an instance file area
function instance_copyfilein($moduleid, $courseid, $filearea, $filepath,$newpath, $requestid){
	global $CFG, $DB;
	
	//do the copying and fetch back the result
	$return = do_copyfilein($moduleid, $courseid, $filearea, $filepath,$newpath, $requestid);
	
	$xml_output = prepareXMLReturn($return, $requestid);

	//Return the data
	return $xml_output;
	
}




//Fetch a sub directory list for file explorer  
//calls itself recursively, dangerous #NOTES instance_copyin needs to made modular with wrapper functions for diff ret types
//wiring up to these functions from gui and form data not done yet 20110919
function instance_copydircontents($moduleid, $courseid, $filearea, $dir,$newpath, $requestid,  $recursive=false){
	global $CFG;
	
	//new return values array
	$dirreturn = fetchReturnArray(true);
	
	$fullpath = $CFG->{'dataroot'}  . $dir; 
	$files = scandir($fullpath);
	if (!empty($files)) {
        foreach ($files as $afile) {
			if ($afile == "." || $afile == "..") {
				continue;
			}
			
			//differntiate between copying file and copying subdir
			if(is_dir($fullpath."/".$afile) && $recursive){
				//$subsubreturn = do_createdir($moduleid, $courseid, $filearea, $newdir);
				$subreturn =  instance_copydircontents($moduleid, $courseid, $filearea, $dir."/".$afile ,$newpath . "/" . $afile, $requestid,  $recursive);
			}else{
				$subreturn = do_copyfilein($moduleid, $courseid, $filearea, $dir."/". $afile,$newpath, $requestid);
			}
			
			//process return values
			if(!$subreturn['success']){
				$dirreturn = mergeReturnArrays($dirreturn,$subreturn);
			}//end of process returns
		}//end of for each file
	}//end of if empty files

	return $dirreturn;
}



//Fetch a directory list from the repo
function instance_copydirin($moduleid, $courseid, $filearea, $filepath,$newpath, $requestid){
	global $USER, $CFG;	
	
	
	global $basedir;
    global $usecheckboxes;
    global $id;
    
	
	//Handle directories
	$fullpath = $CFG->{'dataroot'}  . $filepath;
	

	
	$files = scandir($fullpath);
	if (!empty($files)) {
	//if (false) {
		$return = instance_copydircontents($moduleid, $courseid, $filearea, $filepath,$newpath, $requestid,true);
		
	}else{
		$return=fetchReturnArray(false);
		array_push($return['messages'],"no files in directory to copy.");
	}
	
	
	//Return the data
	$xml_output = prepareXMLReturn($return,$requestid);
	//$xml_output = "<result>I love you</result>";
	return $xml_output;
}

//this turns our results array into an xml string for returning to browser
function prepareXMLReturn($resultArray, $requestid){
	//set up xml to return	
	$xml_output = "<result requestid='" . $requestid . "'>";

		if($resultArray['success']){
			$xml_output .= 'success';
		}else{
			$xml_output .= 'failure';
			foreach ($resultArray['messages'] as $message) {
				$xml_output .= '<error>' . $message . '</error>';
			}
		}
	
	
	//close off xml to return	
	$xml_output .= "</result>\n";	
	return $xml_output;
}


//this merges two result arrays, mostly for use with actions across recursive directories.
function mergeReturnArrays($return1,$return2){
	$return1['success'] = $return1['success'] && $return2['success']; 
	//process return values
	if(!$return1['success'] && !$return2['success']){
		foreach ($return2['messages'] as $message) {
			array_push($return1['messages'],$message);
		}
	}
	return $return1;
}

//this initialises and returns a results array
function fetchReturnArray($initsuccess=false){
	//new filearray
	$return = array();
	$return['messages'] = array();
	$return['success'] = $initsuccess;
	return $return;
}

//The basename function is unreliable with multibyte strings
//This may be *nix dependant ... need a windows condition ..
function poodllBasename($filepath){
	return end(explode(DIRECTORY_SEPARATOR,$filepath));
	//return basename($filepath,'/');
	//return("house");
}

function cmpFilenames($a, $b)
{
    return strcasecmp($a->get_filename(), $b->get_filename());
}

function cmpDirnames($a, $b)
{
    return strcasecmp(poodllBasename($a['dirfile']->get_filepath()), poodllBasename($b['dirfile']->get_filepath()));
}

	
?>
