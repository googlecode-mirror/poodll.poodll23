<?php  // $Id: poodllresourcelib.php,v 1.119.2.13 2008/07/10 09:48:44 scyrma Exp $
/**
 * Code for PoodLL clients(widgets), in particular filter setup and plumbing.
 *
 *
 * @author Justin Hunt
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

/**
 * Show a mediaplayer loaded with a media
 *
 * @param integer $mediaid The id of the media to show
 */
 
define('TEACHERSTREAMNAME','voiceofauthority');
//some constants for the type of media  resource
define('MR_TYPEVIDEO',0);
define('MR_TYPEAUDIO',1);
define('MR_TYPETALKBACK',2);
 
require_once($CFG->dirroot . '/filter/poodll/poodllinit.php');
require_once($CFG->dirroot . '/filter/poodll/Browser.php');
global $PAGE;
//$PAGE->requires->js(new moodle_url($CFG->httpswwwroot . '/mod/assignment/type/poodllonline/swfobject.js'));
//$PAGE->requires->js(new moodle_url($CFG->httpswwwroot . '/mod/assignment/type/poodllonline/javascript.php'));
$PAGE->requires->js(new moodle_url($CFG->httpswwwroot . '/filter/poodll/flash/swfobject.js'));
$PAGE->requires->js(new moodle_url($CFG->httpswwwroot . '/filter/poodll/flash/javascript.php'));

//added for moodle 2
require_once($CFG->libdir . '/filelib.php');


function fetch_slidemenu($runtime){
	global $CFG, $USER, $COURSE;

	if (!empty($USER->username)){
		$mename=$USER->username;
	}else{
		$mename="guest_" + rand(100000, 999999);
	}

	$flvserver = $CFG->poodll_media_server;
	$homeurl = $CFG->wwwroot ;
	$courseid =$COURSE->id;

	

		$partone= '<script type="text/javascript">
						lzOptions = { ServerRoot: \'\'};
				</script>';
		$parttwo = '<script type="text/javascript" src="' . $CFG->wwwroot . '/filter/poodll/flash/embed-compressed.js"></script>';
		$partthree =	'<script type="text/javascript">
				lz.embed.swf({url: \'' . $CFG->wwwroot . '/filter/poodll/flash/slidemenu.lzx.swf9.swf?bcolor=0xFF0000&lzproxied=false&slidewidth=247&slideheight=96&red5url='.urlencode($flvserver). 
							'&homeurl=' . $homeurl .  '&courseid=' . $courseid .  
							'&lzproxied=false\', bgcolor: \'#cccccc\', width: \'400\', height: \'96\', id: \'lzapp_slide_' . rand(100000, 999999) . '\', accessible: \'false\'});       
			</script>
			<noscript>
				Please enable JavaScript in order to use this application.
			</noscript>';
		
		return $partone . $parttwo . $partthree;

}


function fetch_poodllconsole($runtime, $coursedataurl="",$mename="", $courseid=-1, $embed=false){
	global $CFG, $USER, $COURSE;
	
	$broadcastkey="1234567";

	//Set the camera prefs
	$capturewidth=$CFG->filter_poodll_capturewidth;
	$captureheight=$CFG->filter_poodll_captureheight;
	$capturefps=$CFG->filter_poodll_capturefps;
	$prefcam=$CFG->filter_poodll_screencapturedevice;
	$prefmic=$CFG->filter_poodll_studentmic;
	$bandwidth=$CFG->filter_poodll_bandwidth;
	$picqual=$CFG->filter_poodll_picqual; 
	$cameraprefs= '&capturefps=' . $capturefps . '&captureheight=' . $captureheight . '&picqual=' . $picqual . '&bandwidth=' . $bandwidth . '&capturewidth=' . $capturewidth .   '&prefmic=' . $prefmic . '&prefcam=' . $prefcam;
	$flvserver = $CFG->poodll_media_server;
	$teacherpairstreamname="voiceofauthority";


	if ($mename=="" && !empty($USER->username)){
		$mename=$USER->username;
		$mefullname=fullname($USER);
		$mepictureurl=fetch_user_picture($USER,35);
	}

	//if courseid not passed in, try to get it from global
	if ($courseid==-1){
		$courseid=$COURSE->id;
	}
	
	//put in a coursedataurl if we need one
	if ($coursedataurl=="") $coursedataurl= $CFG->wwwroot . '/lib/poodlllogiclib.php%3F';
	
	
	//Show the buttons window if we are admin
	//Also won't receive messages intended for students if we are admin. Be aware.
	if (has_capability('mod/quiz:preview', get_context_instance(CONTEXT_COURSE, $COURSE->id))){		
		$am="admin";
	}else{
		$am="0";
	}


		//here we setup the url and params for the admin console
		$baseUrl = $CFG->wwwroot . '/filter/poodll/flash/poodllconsole.lzx.swf9.swf';
		$params= '?red5url='.urlencode($flvserver). 
							'&mename=' . $mename . '&courseid=' . $courseid .  
							'&teacherpairstreamname=' . $teacherpairstreamname . 
							$cameraprefs .
							'&coursedataurl=' . $coursedataurl . '&broadcastkey=' . $broadcastkey .
							'&lzr=swf9&runtime=swf9';

		//if we are embedding, here we wrap the url and params in the necessary javascript tags
		//otherwise we just return the url and params.
		//embed code is called from poodlladminconsole.php
		if($embed){
				$partone= '<script type="text/javascript">lzOptions = { ServerRoot: \'\'};</script>';
				$parttwo = '<script type="text/javascript" src="' . $CFG->wwwroot . '/filter/poodll/flash/embed-compressed.js"></script>';
				$partthree='<script type="text/javascript">lz.embed.swf({url: \'' . $baseUrl . $params. 
						'\' , width: \'1000\', height: \'750\', id: \'lzapp_admin_console\', accessible: \'false\'});
							</script>
						<noscript>
							Please enable JavaScript in order to use this application.
						</noscript>';
				return $partone . $parttwo . $partthree;
		}else{
			return $baseUrl . $params;					
		}				

}

function fetch_poodllheader($runtime){
	global $CFG, $USER, $COURSE;

	if (!empty($USER->username)){
		$mename=$USER->username;
	}else{
		$mename="guest_" + rand(100000, 999999);
	}
	$coursedataurl=$CFG->wwwroot . "/lib/poodlllogiclib.php";
	$flvserver = $CFG->poodll_media_server;
	$bcsturl =urlencode(fetch_screencast_subscribe($runtime,$mename));
	//$clnturl =urlencode(fetch_clientconsole($coursedataurl,,false));
	$clnturl =urlencode($CFG->wwwroot . '/lib/' . 'poodllclientconsole.php?coursedataurl=' . urlencode($coursedataurl) . '&courseid=' . $COURSE->id);
	$bcstadmin =urlencode(fetch_screencast_broadcast($runtime,$mename));
	$pairsurl =urlencode(fetch_pairclient($runtime,$mename));
	$interviewurl=urlencode(fetch_interviewclient($runtime,$mename));
	$jumpurl=urlencode(fetch_jumpmaker($runtime,$mename));
	$showwidth=$CFG->filter_poodll_showwidth;
	$showheight=$CFG->filter_poodll_showheight;
	
	//Show the buttons window if we are admin
	//Also won't receive messages intended for students if we are admin. Be aware.
	if (has_capability('mod/quiz:preview', get_context_instance(CONTEXT_COURSE, $COURSE->id))){		
		$am="admin";
	}else{
		$am="0";
	}

		$partone= '<script type="text/javascript">
						lzOptions = { ServerRoot: \'\'};
				</script>';
		$parttwo = '<script type="text/javascript" src="' . $CFG->wwwroot . '/filter/poodll/flash/embed-compressed.js"></script>';
		$partthree =	'<script type="text/javascript">
				lz.embed.swf({url: \'' . $CFG->wwwroot . '/filter/poodll/flash/poodllheader.lzx.swf9.swf?bcolor=0xFF0000&lzproxied=false&red5url='.urlencode($flvserver). 
							'&mename=' . $mename . '&courseid=' . $COURSE->id .  '&clnturl=' . $clnturl . '&bcsturl=' . $bcsturl . '&bcstadmin=' . $bcstadmin . '&pairsurl=' . $pairsurl . '&interviewurl=' . $interviewurl . '&jumpurl=' . $jumpurl . '&broadcastheight=' . $showheight . 
							'&lzproxied=false\', bgcolor: \'#cccccc\', width: \'2\', height: \'2\', id: \'lzapp_poodllheader_' . rand(100000, 999999) . '\', accessible: \'false\'});       
			</script>
			<noscript>
				Please enable JavaScript in order to use this application.
			</noscript>';
		
		return $partone . $parttwo . $partthree;

}


//this is the code to get the embed code for the poodllpairwork client
//We separate the embed and non embed into two functions 
//unlike with clientconsole and adminconsole, because of the need for width and height params.
function fetch_embeddablepairclient($runtime, $width,$height,$chat,$whiteboard, $showvideo,$whiteboardback,$useroles=false){
global $CFG;
//laszlo client expects "true" or "false"  so this line is defunct. Thoug we need to standardise how we do this. 
//$showvideo = ($showvideo=="true");
 return('
        <script type="text/javascript">
            lzOptions = { ServerRoot: \'\'};
        </script>
        <script type="text/javascript" src="' . $CFG->wwwroot . '/filter/poodll/flash/embed-compressed.js"></script>
        <script type="text/javascript">
              lz.embed.swf({url: \'' . fetch_pairclient($runtime,$chat,$whiteboard, $showvideo,$whiteboardback,$useroles) . '\', bgcolor: \'#cccccc\', width: \''. $width . '\', height: \'' . $height .'\', id: \'lzapp_' . rand(100000, 999999) . '\', accessible: \'false\'});
        </script>
        <noscript>
            Please enable JavaScript in order to use this application.
        </noscript>
        ');      

}

//this is the code to get a poodllpairwork client for display without embedding
//in the poodll header section of a moodle page as an inline page, or in a popup
function fetch_pairclient($runtime, $chat=true, $whiteboard=true, $showvideo=false,$whiteboardback="", $useroles=false){
	global $CFG, $USER, $COURSE;
	
	if (!empty($USER->username)){
		$mename=$USER->username;
		$mefullname=fullname($USER);
		$mepictureurl=fetch_user_picture($USER,120);
	}else{
		//this is meaningless currently, there is no current way to do pairs
		//with guest. Lets call it "casual poodllpairwork." Butin future it is possible
		$mename="guest_" + rand(100000, 999999);
		$mefullname="guest";
		$mepictureurl="";
	}
	
	//Set the servername
	$flvserver = $CFG->poodll_media_server;
	


	$baseUrl = $CFG->wwwroot . '/filter/poodll/flash/newpairclient.lzx.swf9.swf';
	$params = '?red5url='.urlencode($flvserver) . '&mename=' . $mename . '&mefullname=' . $mefullname . '&mepictureurl=' . $mepictureurl 
			. '&chat=' . $chat  . '&useroles=' . $useroles  . '&whiteboard=' . $whiteboard . '&whiteboardback=' . $whiteboardback . '&showvideo=' . $showvideo  . '&courseid=' . $COURSE->id .'&teacherallstreamname=voiceofauthority&lzproxied=false';
	return $baseUrl . $params;	
}

//this is a stub which we will need to fill in later 
//with the real code
function fetch_interviewclient($runtime){
	return "";
}

//this is a stub which we will need to fill in later 
//with the real code
function fetch_jumpmaker($runtime){
	global $CFG, $USER;
	
	if (!empty($USER->username)){
		$mename=$USER->username;
	}else{
		$mename="guest_" + rand(100000, 999999);
	}
	
	//Set the servername
	$flvserver = $CFG->poodll_media_server;


	$baseUrl = $CFG->wwwroot . '/filter/poodll/flash/jumpmaker.lzx.swf';
	$params = '?red5url='.urlencode($flvserver) . '&mename=' . $mename;
	return $baseUrl . $params;	
}

function fetch_poodllpalette($runtime, $width=800, $height=300){
global $CFG, $USER, $COURSE;
//Set the servername
$flvserver = $CFG->poodll_media_server;
$width=800;

//$coursefilesurl = $CFG->wwwroot . '/lib/editor/htmlarea/poodll-coursefiles.php?id=' . $COURSE->id;
// The ID of the current module (eg moodleurl/view.php?id=X ) or in edit mode update=X
$moduleid = optional_param('update', "-1", PARAM_INT);    
if($moduleid==-1) {$moduleid = optional_param('id', "-1", PARAM_INT); }
$coursefilesurl = $CFG->wwwroot . '/lib/poodlllogiclib.php?courseid=' . $COURSE->id . '&datatype=instancedirlist&paramone=ignore&paramtwo=content&moduleid=' . $moduleid;

$componentlist = $CFG->wwwroot . '/filter/poodll/flash/componentlist.xml';
$poodlllogicurl = $CFG->wwwroot . '/lib/poodlllogiclib.php';

//Set the camera prefs
$capturewidth=$CFG->filter_poodll_capturewidth;
$captureheight=$CFG->filter_poodll_captureheight;
$capturefps=$CFG->filter_poodll_capturefps;
$prefcam=$CFG->filter_poodll_studentcam;
$prefmic=$CFG->filter_poodll_studentmic;
$bandwidth=$CFG->filter_poodll_bandwidth;
$picqual=$CFG->filter_poodll_picqual; 
$cameraprefs= '&capturefps=' . $capturefps . '&captureheight=' . $captureheight . '&picqual=' . $picqual . '&bandwidth=' . $bandwidth . '&capturewidth=' . $capturewidth .   '&prefmic=' . $prefmic . '&prefcam=' . $prefcam;




		//merge config data with javascript embed code
		$params = array();
		$params['red5url'] = urlencode($flvserver);
		$params['poodlllogicurl'] =  $poodlllogicurl . $cameraprefs ;
		$params['courseid'] = $COURSE->id;
		$params['filename'] = 'amediafile';
		$params['coursefiles'] = urlencode($coursefilesurl) ;
		$params['componentlist'] = urlencode($componentlist);

		
	
    	$returnString=  fetchSWFWidgetCode('poodllpalette.lzx.swf10.swf',
    						$params,$width,$height,'#FFFFFF');

    						
    	return $returnString ;
		

}


function fetch_screencast_subscribe($runtime, $mename="", $embed=false, $width=600, $height=350,$broadcastkey="1234567"){
global $CFG, $USER, $COURSE;
//Set the servername
$flvserver = $CFG->poodll_media_server;


//get my name
if($mename==""){$mename=$USER->username;}

//Set  the display sizes
$showwidth=$width;
if($showwidth==0){$showwidth=$CFG->filter_poodll_showwidth;}

$showheight=$height;
if($showheight==0){$showheight=$CFG->filter_poodll_showheight;}

//get the main url of the screensubcribe client
$baseUrl = $CFG->wwwroot . '/filter/poodll/flash/screensubscribe.lzx.swf9.swf';
$params = '?red5url='.urlencode($flvserver). '&broadcastkey='.$broadcastkey. '&showwidth='.$showwidth. '&showheight='.$showheight.'&courseid='.$COURSE->id  .'&mename='.$mename;
//return $baseUrl . $params;	

	//if necessary return the embed code, otherwise just return the url
	if (!$embed){
		return $baseUrl . $params;
	}else{
	 return('
			<script type="text/javascript">
				lzOptions = { ServerRoot: \'\'};
			</script>
			<script type="text/javascript" src="' . $CFG->wwwroot . '/filter/poodll/flash/embed-compressed.js"></script>
			<script type="text/javascript">
				  lz.embed.swf({url: \'' . $baseUrl . $params . '\', bgcolor: \'#cccccc\', width: \''. ($showwidth+10) . '\', height: \'' . ($showheight+10) .'\', id: \'lzapp_screensubscribe_' . rand(100000, 999999) . '\', accessible: \'false\'});
			</script>
			<noscript>
				Please enable JavaScript in order to use this application.
			</noscript>
			'); 	
	}

}
function fetch_screencast_broadcast($runtime, $mename){
global $CFG, $USER, $COURSE;

//Set the servername
$flvserver = $CFG->poodll_media_server;
$broadcastkey="1234567";
$capturedevice = $CFG->filter_poodll_screencapturedevice;

	$baseUrl = $CFG->wwwroot . '/filter/poodll/flash/screenbroadcast.lzx.swf';
	$params = '?red5url='.urlencode($flvserver). '&broadcastkey='.$broadcastkey. '&capturedevice='.$capturedevice. '&mename='.$mename;
	return $baseUrl . $params;	
}
 
function fetch_teachersrecorder($runtime, $filename="", $updatecontrol){
global $CFG, $USER, $COURSE;

//Set the servername
$flvserver = $CFG->poodll_media_server;
if ($filename == ""){
 $filename = $CFG->filter_poodll_filename;
 }

//Set the camera prefs
$capturewidth=$CFG->filter_poodll_capturewidth;
$captureheight=$CFG->filter_poodll_captureheight;
$capturefps=$CFG->filter_poodll_capturefps;
$prefcam=$CFG->filter_poodll_studentcam;
$prefmic=$CFG->filter_poodll_studentmic;
$bandwidth=$CFG->filter_poodll_bandwidth;
$picqual=$CFG->filter_poodll_picqual; 
$cameraprefs= '&capturefps=' . $capturefps . '&captureheight=' . $captureheight . '&picqual=' . $picqual . '&bandwidth=' . $bandwidth . '&capturewidth=' . $capturewidth .   '&prefmic=' . $prefmic . '&prefcam=' . $prefcam;
 
 
//If we are using course ids then lets do that
//else send -1 to widget (ignore flag)
if ($CFG->filter_poodll_usecourseid){
	$courseid = $COURSE->id;
}else{
	$courseid = -1;
}

	//merge config data with javascript embed code
		$params = array();
		$params['red5url'] = urlencode($flvserver);
		$params['updatecontrol'] = $updatecontrol;
		$params['course'] = $courseid;
		$params['filename'] = $filename . $cameraprefs;
	
		
		
	
    	$returnString=  fetchSWFWidgetCode('PoodLLTeachersRecorder.lzx.swf9.swf',
    						$params,$CFG->filter_poodll_talkbackwidth,$CFG->filter_poodll_talkbackheight,'#CCCCCC');

    						
    	return $returnString ;


}



function fetch_whiteboard($runtime, $boardname, $imageurl="", $slave=false,$rooms="", $width=600,$height=350, $mode='normal',$standalone='false'){
global $CFG, $USER,$COURSE;

//Set the servername 
$flvserver = $CFG->poodll_media_server;



//If standalone, then lets standalonify it
if($standalone == 'true'){
	$boardname="solo";
}


//Determine if we are admin, if necessary , for slave/master mode
	if ($slave && has_capability('mod/quiz:preview', get_context_instance(CONTEXT_COURSE, $COURSE->id))){		
		$slave=false;
	}

//whats my name...? my name goddamit, I can't remember  N A mm eeeE
$mename=$USER->username;		

	//merge config data with javascript embed code
		$params = array();
		$params['red5url'] = urlencode($flvserver);
		$params['mename'] = $mename;
		$params['boardname'] = $boardname;
		$params['imageurl'] = $imageurl;
		$params['courseid'] = $COURSE->id;
		$params['rooms'] = $rooms;

		//Are  we merely a slave to the admin whiteboard ?
		if ($slave){
			$widgetstring=  fetchSWFWidgetCode('scribbleslave.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
		}else{
			//normal mode is a standard scribble with a cpanel
			//simple mode has a simple double click popup menu
			if ($mode=='normal'){
					if($runtime=='js'){
						$widgetstring=  fetchJSWidgetCode('scribbler.lzx.js',
									$params,$width,$height,'#FFFFFF'); 
					}elseif($runtime=='auto'){
						$widgetstring=  fetchAutoWidgetCode('scribbler.lzx.swf9.swf',
									$params,$width,$height,'#FFFFFF'); 
					}else{
						$widgetstring=  fetchSWFWidgetCode('scribbler.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
					}
			}else{
					if($runtime=='js'){
						$widgetstring=  fetchJSWidgetCode('simplescribble.lzx.js',
									$params,$width,$height,'#FFFFFF'); 
					}elseif($runtime=='auto'){
						$widgetstring=  fetchAutoWidgetCode('simplescribble.lzx.swf9.swf',
									$params,$width,$height,'#FFFFFF'); 
					}else{
						$widgetstring=  fetchSWFWidgetCode('simplescribble.lzx.swf9.swf',
								$params,$width,$height,'#FFFFFF');
					}
				
			}
		}
		
		return $widgetstring;
		
	
}



function fetchTalkbackPlayer($runtime, $descriptor_file, $streamtype="rtmp",$recordable="false",$savefolder="default"){
global $CFG, $USER,$COURSE;

//Set the servername 
$flvserver = $CFG->poodll_media_server;

//for now these are fixed, but in future we might add the assignment id to the fileroot and turn off the randomnames
//then it would be reviewable again in the future by the students.
$fileroot= "moddata/talkbackstreams/"  . $savefolder;
if($CFG->filter_poodll_overwrite){
		$randomfnames="false";
	}else{
		$randomfnames="true";
	}


//We need a filepath stub, just in case for http streaming
//and for fetching splash screens from data directory
//We also need a stub for course id, 0 if we are not using it.
//If we are recording we need an rtmp stream
//and that needs to know the course id (or lack of)

if ($CFG->filter_poodll_usecourseid){
	$basefile= $CFG->wwwroot . "/file.php/" .  $COURSE->id . "/" ;
	$courseid=$COURSE->id . "/";
}else{
	$basefile= $CFG->wwwroot . "/file.php/" ;
	$courseid="";
}

		//merge config data with javascript embed code
		$params = array();
		$params['red5url'] = urlencode($flvserver);
		$params['basefile'] = $basefile;
		$params['recordable'] = $recordable;
		$params['fileroot'] = $fileroot;
		$params['randomfnames'] = $randomfnames;
		$params['courseid'] = $courseid;
		$params['username'] = $USER->id;
		$params['streamtype'] = $streamtype;
		$params['mediadescriptor'] = $basefile . $descriptor_file;
		
	
    	$returnString=  fetchSWFWidgetCode('talkback.lzx.swf9.swf',
    						$params,$CFG->filter_poodll_talkbackwidth,$CFG->filter_poodll_talkbackheight,'#FFFFFF');

    						
    	return $returnString ;
		

}

function fetchSimpleAudioRecorder($runtime, $assigname, $userid="", $updatecontrol="saveflvvoice", $filename="",$width="430",$height="220"){
global $CFG, $USER, $COURSE, $PAGE;

//Set the servername 
$flvserver = $CFG->poodll_media_server;
	
//Set the microphone config params
$micrate = $CFG->filter_poodll_micrate;
$micgain = $CFG->filter_poodll_micgain;
$micsilence = $CFG->filter_poodll_micsilencelevel;
$micecho = $CFG->filter_poodll_micecho;
$micloopback = $CFG->filter_poodll_micloopback;
$micdevice = $CFG->filter_poodll_studentmic;

	
	

//If we are using course ids then lets do that
//else send -1 to widget (ignore flag)
if ($CFG->filter_poodll_usecourseid){
	$courseid = $COURSE->id;
}else{
	$courseid = -1;
}

//If no user id is passed in, try to get it automatically
//Not sure if  this can be trusted, but this is only likely to be the case
//when this is called from the filter. ie not from an assignment.
if ($userid=="") $userid = $USER->username;

//Stopped using this 
//$filename = $CFG->filter_poodll_filename;
 $overwritemediafile = $CFG->filter_poodll_overwrite==1 ? "true" : "false" ;
if ($updatecontrol == "saveflvvoice"){
	$savecontrol = "<input name='saveflvvoice' type='hidden' value='' id='saveflvvoice' />";
}else{
	$savecontrol = "";
}

$params = array();
		$params['red5url'] = urlencode($flvserver);
		$params['overwritefile'] = $overwritemediafile;
		$params['rate'] = $micrate;
		$params['gain'] = $micgain;
		$params['prefdevice'] = $micdevice;
		$params['loopback'] = $micloopback;
		$params['echosupression'] = $micecho;
		$params['silencelevel'] = $micsilence;
		$params['filename'] = "123456.flv";
		$params['assigName'] = $assigname;
		$params['course'] = $courseid;
		$params['updatecontrol'] = $updatecontrol;
		$params['uid'] = $userid;
	
    	$returnString=  fetchSWFWidgetCode('PoodLLAudioRecorder.lzx.swf9.swf',
    						$params,$width,$height,'#CFCFCF');
    						
    	$returnString .= 	 $savecontrol;
    						
    	return $returnString ;

}

function fetchAudioRecorderForSubmission($runtime, $assigname, $updatecontrol="saveflvvoice", $contextid,$component,$filearea,$itemid){
global $CFG, $USER, $COURSE;

//Set the servername 
$flvserver = $CFG->poodll_media_server;
//Set the microphone config params
$micrate = $CFG->filter_poodll_micrate;
$micgain = $CFG->filter_poodll_micgain;
$micsilence = $CFG->filter_poodll_micsilencelevel;
$micecho = $CFG->filter_poodll_micecho;
$micloopback = $CFG->filter_poodll_micloopback;
$micdevice = $CFG->filter_poodll_studentmic;

//removed from params to make way for moodle 2 filesystem params Justin 20120213
$userid="dummy";
$width="430";
$height="220";
$filename="12345"; 
$poodllfilelib= $CFG->wwwroot . '/lib/poodllfilelib.php';

//If we are using course ids then lets do that
//else send -1 to widget (ignore flag)
if ($CFG->filter_poodll_usecourseid){
	$courseid = $COURSE->id;
}else{
	$courseid = -1;
} 

//If no user id is passed in, try to get it automatically
//Not sure if  this can be trusted, but this is only likely to be the case
//when this is called from the filter. ie not from an assignment.
if ($userid=="") $userid = $USER->username;

//Stopped using this 
//$filename = $CFG->filter_poodll_filename;
 $overwritemediafile = $CFG->filter_poodll_overwrite==1 ? "true" : "false" ;
if ($updatecontrol == "saveflvvoice"){
	$savecontrol = "<input name='saveflvvoice' type='hidden' value='' id='saveflvvoice' />";
}else{
	$savecontrol = "";
}

$params = array();

		$params['red5url'] = urlencode($flvserver);
		$params['overwritefile'] = $overwritemediafile;
		$params['rate'] = $micrate;
		$params['gain'] = $micgain;
		$params['prefdevice'] = $micdevice;
		$params['loopback'] = $micloopback;
		$params['echosupression'] = $micecho;
		$params['silencelevel'] = $micsilence;
		$params['filename'] = "123456.flv";
		$params['assigName'] = $assigname;
		$params['course'] = $courseid;
		$params['updatecontrol'] = $updatecontrol;
		$params['uid'] = $userid;
		//for file system in moodle 2
		$params['poodllfilelib'] = $poodllfilelib;
		$params['contextid'] = $contextid;
		$params['component'] = $component;
		$params['filearea'] = $filearea;
		$params['itemid'] = $itemid;
	
    	$returnString=  fetchSWFWidgetCode('PoodLLAudioRecorder.lzx.swf9.swf',
    						$params,$width,$height,'#CFCFCF');
    						
    	$returnString .= 	 $savecontrol;
    						
    	return $returnString ;
	

}


function fetch_stopwatch($runtime, $width, $height, $fontheight,$mode='normal',$permitfullscreen=false,$uniquename='uniquename'){
global $CFG, $USER, $COURSE;

//Set the servername 
$flvserver = $CFG->poodll_media_server;

//If we are using course ids then lets do that
//else send -1 to widget (ignore flag)
if ($CFG->filter_poodll_usecourseid){
	$courseid = $COURSE->id;
}else{
	$courseid = -1;
}

//get username automatically
$userid = $USER->username;


	
	//Determine if we are admin, if necessary , for slave/master mode
	if (has_capability('mod/quiz:preview', get_context_instance(CONTEXT_COURSE, $COURSE->id))){		
		$isadmin=true;
	}else{
		$isadmin=false;
	}
	    //merge config data with javascript embed code
		$params = array();
		$params['permitfullscreen'] = $permitfullscreen;
		$params['fontheight'] = $fontheight;
		$params['uniquename'] = $uniquename;
		$params['courseid'] = $courseid;
		$params['red5url'] = urlencode($flvserver);
		$params['mode'] = $mode;
		
		//LZ string if master/save  mode and not admin => show slave mode
	//otherwise show stopwatch
	if ($mode=='master' && !$isadmin) {
    	$returnString=  fetchSWFWidgetCode('slaveview.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
    }elseif($runtime=='swf'){
    	$returnString=  fetchSWFWidgetCode('stopwatch.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
	 }elseif($runtime=='auto'){
    	$returnString=  fetchAutoWidgetCode('stopwatch.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
    }else{
    	$returnString=  fetchJSWidgetCode('stopwatch.lzx.js',
    						$params,$width,$height,'#FFFFFF');
    }
   						
    return $returnString;
    

}

function fetch_poodllcalc($runtime, $width, $height){
global $CFG;

	//merge config data with javascript embed code
		$params = array();
		if($runtime=='js'){
			$returnString=  fetchJSWidgetCode('poodllcalc.lzx.js',
    						$params,$width,$height,'#FFFFFF');
		 }elseif($runtime=='auto'){
							$returnString=fetchAutoWidgetCode('poodllcalc.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
		}else{
    		$returnString=  fetchSWFWidgetCode('poodllcalc.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
    	}
   						
    	return $returnString;

}

function fetch_explorer($runtime, $width, $height, $moduleid=0){
global $CFG,$COURSE;
	
	//If we are using course ids then lets do that
	//else send -1 to widget (ignore flag)
		$courseid = $COURSE->id;

	
	//get the url to the automated medialist maker
	$filedataurl= $CFG->wwwroot . '/lib/poodllfilelib.php';
	$componentlist= $CFG->wwwroot . '/filter/poodll/componentlist.xml';

	//merge config data with javascript embed code
		$params = array();
		$params['courseid'] = $courseid;
		$params['filedataurl'] = $filedataurl;
		$params['componentlist'] = $componentlist;
		$params['moduleid'] = $moduleid;
		
		if($runtime=='js'){
			$returnString=  fetchJSWidgetCode('attachmentexplorer.lzx.js',
    						$params,$width,$height,'#FFFFFF'); 
		}elseif($runtime=='auto'){
			$returnString=  fetchAutoWidgetCode('attachmentexplorer.lzx.swf10.swf',
    						$params,$width,$height,'#FFFFFF');
		}else{
    		$returnString=  fetchSWFWidgetCode('attachmentexplorer.lzx.swf10.swf',
    						$params,$width,$height,'#FFFFFF');
    	}
   						
    	return $returnString;

}

function fetch_countdowntimer($runtime, $initseconds, $usepresets, $width, $height, $fontheight,$mode='normal',$permitfullscreen=false,$uniquename='uniquename'){
global $CFG, $USER, $COURSE;

//Set the servername 
$flvserver = $CFG->poodll_media_server;

//If we are using course ids then lets do that
//else send -1 to widget (ignore flag)
if ($CFG->filter_poodll_usecourseid){
	$courseid = $COURSE->id;
}else{
	$courseid = -1;
}

//get username automatically
$userid = $USER->username;


	
	//Determine if we are admin, if necessary , for slave/master mode
	if (has_capability('mod/quiz:preview', get_context_instance(CONTEXT_COURSE, $COURSE->id))){		
		$isadmin=true;
	}else{
		$isadmin=false;
	}
	
	
	
	
			//merge config data with javascript embed code
		$params = array();
		$params['initseconds'] = $initseconds;
		$params['permitfullscreen'] = $permitfullscreen;
		$params['usepresets'] = $usepresets;
		$params['fontheight'] = $fontheight;
		$params['mename'] = $userid; //this might be wrong, but do we need this?
		$params['uniquename'] = $uniquename;
		$params['courseid'] = $courseid;
		$params['red5url'] = urlencode($flvserver);
		$params['mode'] = $mode;
		
		//LZ string if master/save  mode and not admin => show slave mode
	//otherwise show stopwatch
	if ($mode=='master' && !$isadmin) {
    	$returnString=  fetchSWFWidgetCode('slaveview.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
    }elseif($runtime=='swf'){
    	$returnString=  fetchSWFWidgetCode('countdowntimer.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
	}elseif($runtime=='auto'){
    	$returnString=  fetchAutoWidgetCode('countdowntimer.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
    }else{
    	$returnString=  fetchJSWidgetCode('countdowntimer.lzx.js',
    						$params,$width,$height,'#FFFFFF');
    
    
    }
   						
    	return $returnString;

}

function fetch_counter($runtime, $initcount, $usepresets, $width, $height, $fontheight,$permitfullscreen=false){
global $CFG;

		//merge config data with javascript embed code
		$params = array();
		$params['initcount'] = $initcount;
		$params['permitfullscreen'] = $permitfullscreen;
		$params['usepresets'] = $usepresets;
		$params['fontheight'] = $fontheight;
		
	
    	
    	if($runtime=="swf"){
    		$returnString=  fetchSWFWidgetCode('counter.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
		}elseif($runtime=="auto"){
    		$returnString=  fetchAutoWidgetCode('counter.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
		}else{
			$returnString=  fetchJSWidgetCode('counter.lzx.js',
    						$params,$width,$height,'#FFFFFF');
		}
   						
    	return $returnString;
    	
    	

}

function fetch_dice($runtime, $dicecount,$dicesize,$width,$height){
global $CFG;

		//merge config data with javascript embed code
		$params = array();
		$params['dicecount'] = $dicecount;
		$params['dicesize'] = $dicesize;
		
	if($runtime=="swf"){
    	$returnString=  fetchSWFWidgetCode('dice.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
	}elseif($runtime=="auto"){
    	$returnString=  fetchAutoWidgetCode('dice.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
	}else{
		$returnString=  fetchJSWidgetCode('dice.lzx.js',
    						$params,$width,$height,'#FFFFFF');
	}
    	

    						
    	return $returnString ;

}

function fetch_flashcards($runtime, $cardset,$cardwidth,$cardheight,$randomize,$width,$height){
global $CFG,$COURSE;


	//determine which of, automated or manual cardsets to use
	if(strlen($cardset) > 4 && substr($cardset,0,4)=='http'){
		$fetchdataurl=$cardset;
	}elseif(strlen($cardset) > 4 && substr($cardset,-4)==".xml"){
		//get a manually made playlist
		$fetchdataurl= $CFG->wwwroot . "/file.php/" .  $COURSE->id . "/" . $cardset;
	}else{
		//get the url to the automated medialist maker
		$fetchdataurl= $CFG->wwwroot . '/lib/poodlllogiclib.php?datatype=poodllflashcards&courseid=' . $COURSE->id 
			. '&paramone=' . $cardset 
			. '&cachekiller=' . rand(10000,999999);
	}
	

		//merge config data with javascript embed code
		$params = array();
		$params['cardset'] = urlencode($fetchdataurl);
		$params['randomize'] = $randomize;
		$params['cardwidth'] = $cardwidth;
		$params['cardheight'] = $cardheight;
		
	if($runtime=="js"){
    	$returnString=  fetchJSWidgetCode('flashcards.lzx.js',
    						$params,$width,$height,'#FFFFFF');
	}elseif($runtime=="auto"){
    	$returnString=  fetchAutoWidgetCode('flashcards.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
	
	}else{
		$returnString=  fetchSWFWidgetCode('flashcards.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
	}
    						
    	return $returnString ;

}


function fetchSimpleVideoRecorder($runtime, $assigname, $userid="", $updatecontrol="saveflvvoice", $filename="", $width="350",$height="400"){
global $CFG, $USER, $COURSE;

//Set the servername and a capture settings from config file
$flvserver = $CFG->poodll_media_server;
$capturewidth=$CFG->filter_poodll_capturewidth;
$captureheight=$CFG->filter_poodll_captureheight;
$capturefps=$CFG->filter_poodll_capturefps;
$prefcam=$CFG->filter_poodll_studentcam;
$prefmic=$CFG->filter_poodll_studentmic;
$bandwidth=$CFG->filter_poodll_bandwidth;
$picqual=$CFG->filter_poodll_picqual;

//If we are using course ids then lets do that
//else send -1 to widget (ignore flag)
if ($CFG->filter_poodll_usecourseid){
	$courseid = $COURSE->id;
}else{
	$courseid = -1;
}

//If no user id is passed in, try to get it automatically
//Not sure if  this can be trusted, but this is only likely to be the case
//when this is called from the filter. ie not from an assignment.
if ($userid=="") $userid = $USER->username;

//Stopped using this 
//$filename = $CFG->filter_poodll_filename;
 $overwritemediafile = $CFG->filter_poodll_overwrite==1 ? "true" : "false" ;
if ($updatecontrol == "saveflvvoice"){
	$savecontrol = "<input name='saveflvvoice' type='hidden' value='' id='saveflvvoice' />";
}else{
	$savecontrol = "";
}

$params = array();
		$params['red5url'] = urlencode($flvserver);
		$params['overwritefile'] = $overwritemediafile;
		$params['capturefps'] = $capturefps;
		$params['filename'] = $filename;
		$params['assigName'] = $assigname;
		$params['captureheight'] = $captureheight;
		$params['picqual'] = $picqual;
		$params['bandwidth'] = $bandwidth;
		$params['capturewidth'] = $capturewidth;
		$params['prefmic'] = $prefmic;
		$params['prefcam'] = $prefcam;
		$params['course'] = $courseid;
		$params['updatecontrol'] = $updatecontrol;
		$params['uid'] = $userid;
	
    	$returnString=  fetchSWFWidgetCode('PoodLLVideoRecorder.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
    						
    	$returnString .= 	$savecontrol;
    						
    	return $returnString ;
	

}

function fetchVideoRecorderForSubmission($runtime, $assigname, $updatecontrol="saveflvvoice", $contextid,$component,$filearea,$itemid){
global $CFG, $USER, $COURSE;

//Set the servername and a capture settings from config file
$flvserver = $CFG->poodll_media_server;
$capturewidth=$CFG->filter_poodll_capturewidth;
$captureheight=$CFG->filter_poodll_captureheight;
$capturefps=$CFG->filter_poodll_capturefps;
$prefcam=$CFG->filter_poodll_studentcam;
$prefmic=$CFG->filter_poodll_studentmic;
$bandwidth=$CFG->filter_poodll_bandwidth;
$picqual=$CFG->filter_poodll_picqual;

//removed from params to make way for moodle 2 filesystem params Justin 20120213
$userid="dummy";
$width="350";
$height="400";
$filename="12345"; 
$poodllfilelib= $CFG->wwwroot . '/lib/poodllfilelib.php';

//If we are using course ids then lets do that
//else send -1 to widget (ignore flag)
if ($CFG->filter_poodll_usecourseid){
	$courseid = $COURSE->id;
}else{
	$courseid = -1;
} 

//If no user id is passed in, try to get it automatically
//Not sure if  this can be trusted, but this is only likely to be the case
//when this is called from the filter. ie not from an assignment.
if ($userid=="") $userid = $USER->username;

//Stopped using this 
//$filename = $CFG->filter_poodll_filename;
 $overwritemediafile = $CFG->filter_poodll_overwrite==1 ? "true" : "false" ;
if ($updatecontrol == "saveflvvoice"){
	$savecontrol = "<input name='saveflvvoice' type='hidden' value='' id='saveflvvoice' />";
}else{
	$savecontrol = "";
}

$params = array();
		$params['red5url'] = urlencode($flvserver);
		$params['overwritefile'] = $overwritemediafile;
		$params['capturefps'] = $capturefps;
		$params['filename'] = $filename;
		$params['assigName'] = $assigname;
		$params['captureheight'] = $captureheight;
		$params['picqual'] = $picqual;
		$params['bandwidth'] = $bandwidth;
		$params['capturewidth'] = $capturewidth;
		$params['prefmic'] = $prefmic;
		$params['prefcam'] = $prefcam;
		$params['course'] = $courseid;
		$params['updatecontrol'] = $updatecontrol;
		$params['uid'] = $userid;
		//for file system in moodle 2
		$params['poodllfilelib'] = $poodllfilelib;
		$params['contextid'] = $contextid;
		$params['component'] = $component;
		$params['filearea'] = $filearea;
		$params['itemid'] = $itemid;
	
    	$returnString=  fetchSWFWidgetCode('PoodLLVideoRecorder.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
    						
    	$returnString .= 	$savecontrol;
    						
    	return $returnString ;
	

}

//Audio playltest player with defaults, for use with directories of audio files
function fetchAudioTestPlayer($runtime, $playlist,$protocol="", $width="400",$height="150"){
global $CFG, $USER, $COURSE;

$moduleid = optional_param('id', 0, PARAM_INT);    // The ID of the current module (eg moodleurl/view.php?id=X )

//Set our servername .
$flvserver = $CFG->poodll_media_server;


//determine which of, automated or manual playlists to use
if(strlen($playlist) > 4 && substr($playlist,-4)==".xml"){
	//get a manually made playlist
	$fetchdataurl= $CFG->wwwroot . "/file.php/" .  $courseid . "/" . $playlist;
}else{
	//get the url to the automated medialist maker
	$fetchdataurl= $CFG->wwwroot . '/lib/poodlllogiclib.php?datatype=poodllaudiolist'
		. '&courseid=' . $COURSE->id
		. '&moduleid=' . $moduleid
		. '&paramone=' . $playlist 
		. '&paramtwo=' . $protocol 
		. '&paramthree=' . $filearea
		. '&cachekiller=' . rand(10000,999999);
}

	
		$params = array();
		$params['red5url'] = urlencode($flvserver);
		$params['playertype'] = $protocol;
		$params['playlist']=urlencode($fetchdataurl);
	
    	$returnString=  fetchSWFWidgetCode('poodllaudiotestplayer.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
    						
    	return $returnString;


	
}


//Audio playlist player with defaults, for use with directories of audio files
function fetchAudioListPlayer($runtime, $playlist, $filearea="content",$protocol="", $width="400",$height="350",$sequentialplay="true"){
global $CFG, $USER, $COURSE;

$moduleid = optional_param('id', 0, PARAM_INT);    // The ID of the current module (eg moodleurl/view.php?id=X )

//Set our servername .
$flvserver = $CFG->poodll_media_server;


//determine which of, automated or manual playlists to use
if(strlen($playlist) > 4 && substr($playlist,-4)==".xml"){
	//get a manually made playlist
	$fetchdataurl= $CFG->wwwroot . "/file.php/" .  $courseid . "/" . $playlist;
}else{
	//get the url to the automated medialist maker
	$fetchdataurl= $CFG->wwwroot . '/lib/poodlllogiclib.php?datatype=poodllaudiolist'
		. '&courseid=' . $COURSE->id
		. '&moduleid=' . $moduleid
		. '&paramone=' . $playlist 
		. '&paramtwo=' . $protocol 
		. '&paramthree=' . $filearea
		. '&cachekiller=' . rand(10000,999999);
}

	

	$params = array();
		$params['red5url'] = urlencode($flvserver);
		$params['playertype'] = $protocol;
		$params['sequentialplay'] = $sequentialplay;
		$params['playlist']=urlencode($fetchdataurl);
	
    	$returnString=  fetchSWFWidgetCode('poodllaudiolistplayer.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
    						
    	return $returnString;


	
}

//Audio player with defaults, for use with PoodLL filter
function fetchSimpleAudioPlayer($runtime, $rtmp_file, $protocol="", $width="450",$height="40",$embed=false, $embedstring="Play",$permitfullscreen=false){
global $CFG, $USER, $COURSE;

//Set our servername .
$flvserver = $CFG->poodll_media_server;
$courseid= $COURSE->id;

	//Set our use protocol type
	//if one was not passed, then it may have been tagged to the url
	//this was the old way.
	if ($protocol==""){
		$type = "rtmp";
		if (strlen($rtmp_file) > 5){
			$protocol = substr($rtmp_file,0,5);
			switch ($protocol){
				case "yutu:":
					$rtmp_file = substr($rtmp_file,5);
					$rtmp_file = getYoutubeLink($rtmp_file);
					$type="http";
					break;			
				case "http:":
					$rtmp_file = substr($rtmp_file,5);
					$type="http";
					break;		
				case "rtmp:":
					$rtmp_file = substr($rtmp_file,5);
				default:
					$type="rtmp";				

			}
		
		}//end of if strlen(rtmpfile) > 4

	//If we have one passed in, lets set it to our type
	}else{
		switch ($protocol){
				case "yutu":
					$rtmp_file = getYoutubeLink($rtmp_file);
					$type="http";
					break;			
				case "http":
				case "rtmp":
				default:
					$type=$protocol;				

			}
	}



	//some common variables for the embedding stage.	
	$playerLoc = $CFG->wwwroot . '/filter/poodll/flash/poodllaudioplayer.lzx.swf9.swf';

	//If we are using the legacy coursefiles, we want to fall into this code
	//this is just a temporary fix to achieve this. Justin 20111213
	if($protocol=='rtmp'){
		$rtmp_file= $CFG->wwwroot . "/file.php/" .  $courseid . "/" . $rtmp_file;
        $type = 'http';
	}
	
	//If we want to avoid javascript we do it this way
	//embedding via javascript screws updating the entry on the page,
	//which is seen after marking a single audio assignment from a list
	if ($embed ){
		$lzid = "lzapp_audioplayer_" . rand(100000, 999999) ;
		$returnString="		
		 <div id='$lzid' class='player'>
        <a href='#' onclick=\"javascript:loadAudioPlayer('$rtmp_file', '$lzid', 'sample_$lzid', '$width', '$height'); return false;\">$embedstring </a>
      </div>		
		";


			return $returnString;

	//if we do not want to use embedding, ie use javascript to detect and insert (probably best..?)	
	}else{
	
	
		$params = array();
		$params['red5url'] = urlencode($flvserver);
		$params['playertype'] = $type;
		$params['mediapath'] = $rtmp_file;
		$params['permitfullscreen'] = $permitfullscreen;
	
	
		if($runtime=='js'){
				$returnString="";
				
				//The HTML5 Code
				$returnString .="<audio controls width='" . $width . "' height='" . $height . "'>
								<source src='" .$rtmp_file . "'/>
								</audio>";
				
				//=======================
				//if we are using mediaelement js use this. We use JQuery which is not ideal, in moodle yui environment
				//$mediajsroot = $CFG->wwwroot . '/filter/poodll/js/mediaelementjs/';
				//$returnString .="<script src='" . $mediajsroot .  "jquery.js'></script>";
				//$returnString .="<script src='" . $mediajsroot .  "mediaelement-and-player.min.js'></script>";
				//$returnString .="<link rel='stylesheet' href='" . $mediajsroot .  "mediaelementplayer.css' />	";
				//$returnString .="<script src='" . $mediajsroot .  "mep-feature-loop.js'></script>";
				//$returnString .="<script src='" . $mediajsroot .  "mep-feature-speed.js'></script>";
				//$returnString .="<script src='" . $mediajsroot .  "mep-feature-progress.js'></script>";
				////$returnString .="<script>$('audio,video').mediaelementplayer({features:['playpause','loop','speed','progess','volume']});</script>";
				//$returnString .="<script>$('audio,video').mediaelementplayer();</script>";
				//=======================
			
				//=======================
				//If we use Kaltura, use this			
				//$returnString .="<script src='http://html5.kaltura.org/js'></script>";
				//=======================
				
				


			
				
							
		}else{
	
				$returnString=  fetchSWFWidgetCode('poodllaudioplayer.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
							
		}
    						
    	return $returnString;
	}
	

	
}



//Video player with defaults, for use with PoodLL filter
function fetchSimpleVideoPlayer($runtime, $rtmp_file, $width="400",$height="380",$protocol="",$embed=false,$permitfullscreen=false, $embedstring="Play"){
global $CFG, $USER, $COURSE;

//Set our servername .
$flvserver = $CFG->poodll_media_server;
$courseid= $COURSE->id;


	//Massage the media file name if we have a username variable passed in.	
	//This allows us to show different video to each student
	$rtmp_file = str_replace( "@@username@@",$USER->username,$rtmp_file);
	
	//Determine if we are admin, admins can always fullscreen
	if (has_capability('mod/quiz:preview', get_context_instance(CONTEXT_COURSE, $COURSE->id))){		
		$permitfullscreen='true';
	}


	//Set our use protocol type
	//if one was not passed, then it may have been tagged to the url
	//this was the old way.
	if ($protocol==""){
		$type = "rtmp";
		if (strlen($rtmp_file) > 5){
			$protocol = substr($rtmp_file,0,5);
			switch ($protocol){
				case "yutu:":
					$rtmp_file = substr($rtmp_file,5);
					$type="yutu";
					break;			
				case "http:":
					$rtmp_file = substr($rtmp_file,5);
					$type="http";
					break;		
				case "rtmp:":
					$rtmp_file = substr($rtmp_file,5);
				default:
					$type="rtmp";				

			}
		
		}//end of if strlen(rtmpfile) > 4

	//If we have one passed in, lets set it to our type
	}else{
		switch ($protocol){
				case "yutu":		
				case "http":
				case "rtmp":
				default:
					$type=$protocol;				

			}
	}
	
	//If we are using the legacy coursefiles, we want to fall into this code
	//this is just a temporary fix to achieve this. Justin 20111213
	if($protocol=='rtmp'){
		$rtmp_file= $CFG->wwwroot . "/file.php/" .  $courseid . "/" . $rtmp_file;
        $type = 'http';
	}
	
	//If we want to avoid loading multiple players on the screen, we use this script
	//to load players ondemand
	//this does screw up updating the entry on the page,
	//which is seen after marking a single audio/vide assignment and returning to the list
	//poodllonline assignment
	if ($embed){
		$lzid = "lzapp_videoplayer_" . rand(100000, 999999) ;
		$returnString="		
	  <div id='$lzid' class='player'>
        <a href='#' onclick=\"javascript:loadVideoPlayer('$rtmp_file', '$lzid', 'sample_$lzid', '$width', '$height'); return false;\">$embedstring </a>
      </div>		
		";
	

			return $returnString;

	}else{		
	
 		$params = array();
		$params['red5url'] = urlencode($flvserver);
		$params['playertype'] = $type;
		$params['mediapath'] = $rtmp_file;
		$params['permitfullscreen'] = $permitfullscreen;
	
		if($runtime=='js'){
			$returnString="";
			
			$poster="";//To do add poster code, once we have thought it all through a bit better
			$returnString .="<video controls poster='" . $poster . "' width='" . $width . "' height='" . $height . "'>
								<source type='video/mp4' src='" .$rtmp_file . "'/>
							</video>";
			//============================
			//if we are using mediaelement js use this
			//$mediajsroot = $CFG->wwwroot . '/filter/poodll/js/mediaelementjs/';
			//$returnString .="<script src='" . $mediajsroot .  "jquery.js'></script>";
			//$returnString .="<script src='" . $mediajsroot .  "mediaelement-and-player.min.js'></script>";
			//$returnString .="<link rel='stylesheet' href='" . $mediajsroot .  "mediaelementplayer.css' />	";
			//$returnString .="<script src='" . $mediajsroot .  "mep-feature-loop.js'></script>";
			//$returnString .="<script src='" . $mediajsroot .  "mep-feature-speed.js'></script>";
			//$returnString .="<script>$('audio,video').mediaelementplayer({features:['playpause','loop','speed','progess','volume']});</script>";
			////$returnString .="<script> $('audio,video').mediaelementplayer(); </script>";
			//============================
			
			//============================
			//If we use Kaltura, use this			
			//$returnString .="<script src='http://html5.kaltura.org/js'></script>";		
			//============================
							
		}else{
	
			$returnString=  fetchSWFWidgetCode('poodllvideoplayer.lzx.swf9.swf',
    						$params,$width,$height,'#FFFFFF');
    	}					
							
    	return $returnString;

		}
	
}




function fetchSmallVideoGallery($runtime, $playlist, $filearea="content", $protocol="", $width, $height,$permitfullscreen=false){
global $CFG, $USER, $COURSE;

//Set the servername 
$courseid= $COURSE->id;
$flvserver = $CFG->poodll_media_server;

$moduleid = optional_param('id', 0, PARAM_INT);    // The ID of the current module (eg moodleurl/view.php?id=X )

//set size params
if ($width==''){$width=$CFG->filter_poodll_smallgallwidth;}
if ($height==''){$height=$CFG->filter_poodll_smallgallheight;}

//Determine if we are admin, admins can always fullscreen
	if (has_capability('mod/quiz:preview', get_context_instance(CONTEXT_COURSE, $COURSE->id))){		
		$permitfullscreen='true';
	}


//determine which of, automated or manual playlists to use
if(strlen($playlist) > 4 && substr($playlist,-4)==".xml"){
	//get a manually made playlist
	$fetchdataurl= $CFG->wwwroot . "/file.php/" .  $courseid . "/" . $playlist;
}else{
	
	//get the url to the automated medialist maker
	$fetchdataurl= $CFG->wwwroot . '/lib/poodlllogiclib.php?datatype=poodllmedialist'
		. '&courseid=' . $COURSE->id
		. '&moduleid=' . $moduleid
		. '&paramone=' . $playlist 
		. '&paramtwo=' . $protocol 
		. '&paramthree=' . $filearea
		. '&cachekiller=' . rand(10000,999999);
}
 	
 	$params = array();
	$params['red5url'] = urlencode($flvserver);
	$params['playlist'] = urlencode($fetchdataurl);
	$params['protocol'] = urlencode($protocol);
	$params['permitfullscreen'] = urlencode($permitfullscreen);

    $returnString=  fetchSWFWidgetCode('smallvideogallery.lzx.swf9.swf',
    						$params,$width,$height,'#D5FFFA');

	return $returnString;
		
		
}

function fetchBigVideoGallery($runtime, $playlist,$filearea="content",  $protocol, $width, $height){
global $CFG, $USER, $COURSE;

//Set the servername 
$courseid= $COURSE->id;
$flvserver = $CFG->poodll_media_server;

$moduleid = optional_param('id', 0, PARAM_INT);    // The ID of the current module (eg moodleurl/view.php?id=X )

//set size params
if ($width==''){$width=$CFG->filter_poodll_biggallwidth;}
if ($height==''){$height=$CFG->filter_poodll_biggallheight;}

//determine which of, automated or manual playlists to use
if(strlen($playlist) > 4 && substr($playlist,-4)==".xml"){
	//get a manually made playlist
	$fetchdataurl= $CFG->wwwroot . "/file.php/" .  $courseid . "/" . $playlist;
}else{
	//get the url to the automated medialist maker
		//get the url to the automated medialist maker
	$fetchdataurl= $CFG->wwwroot . '/lib/poodlllogiclib.php?datatype=poodllmedialist'
		. '&courseid=' . $COURSE->id
		. '&moduleid=' . $moduleid
		. '&paramone=' . $playlist 
		. '&paramtwo=' . $protocol 
		. '&paramthree=' . $filearea
		. '&cachekiller=' . rand(10000,999999);
}

	$params = array();
	$params['red5url'] = urlencode($flvserver);
	$params['playlist'] = urlencode($fetchdataurl);

	if($runtime=='swf'){
		//set the flash widget suffix
		$widget = "bigvideogallery.lzx.swf9.swf";
    	$returnString=  fetchSWFWidgetCode($widget, $params,$width,$height,'#D5FFFA');
	}else{
		//set the JS widget suffix
		$widget = "bigvideogallery.lzx.js";
		$returnString=  fetchJSWidgetCode($widget,$params,$width,$height,'#D5FFFA');
	}
	
	return $returnString;

}


//WMV player with defaults, for use with PoodLL filter
function fetchWMVPlayer($wmv_file, $width="400",$height="380"){
global $CFG, $USER, $COURSE;

	//Massage the media file name if we have a username variable passed in.	
	//This allows us to show different video to each student
	$wmv_file = str_replace( "@@username@@",$USER->username,$wmv_file);



	//Add course id and full path to url 
	$wmv_file= $CFG->wwwroot . "/file.php/" . $COURSE->id . "/" .   $wmv_file ;

	
		 return("
				<table><tr><td> 
					<object id='MediaPlayer' width=$width height=$height classid='CLSID:22D6f312-B0F6-11D0-94AB-0080C74C7E95' standby='Loading Windows Media Player components...' type='application/x-oleobject' codebase='http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,7,1112'>
						<param name='filename' value='$wmv_file'>
						<param name='Showcontrols' value='True'>
						<param name='autoStart' value='False'>
						<param name='wmode' value='transparent'>
						<embed type='application/x-mplayer2' src='$wmv_file' name='MediaPlayer' autoStart='True' wmode='transparent' width='$width' height='$height' ></embed>
					</object>										
				</td></tr></table>"); 
		
	
}

	
//Given a user object, return the url to a picture for that user.
function fetch_user_picture($user,$size){
global $CFG;

	//get default sizes for non custom pics
    if (empty($size)) {
		//size = 35;
        $file = 'f2';        
    } else if ($size === true or $size == 1) {
        //size = 100;
		$file = 'f1';        
    } else if ($size >= 50) {
        $file = 'f1';
    } else {
        $file = 'f2';
    }
	
	//now get the url for the pic
    if ($user->picture) {  // Print custom user picture
        require_once($CFG->libdir.'/filelib.php');
        $src = get_file_url($user->id.'/'.$file.'.jpg', null, 'user');
    } else {         // Print default user pictures (use theme version if available)
        $src =  "$CFG->pixpath/u/$file.png";
    }
	return $src;
}

function fetch_filter_properties($filterstring){
	//this just removes the {POODLL: .. } to leave us with the good stuff.	
	//there MUST be a better way than this.
	$rawproperties = explode ("{POODLL:", $filterstring);
	$rawproperties = $rawproperties[1];
	$rawproperties = explode ("}", $rawproperties);	
	$rawproperties = $rawproperties[0];

	//Now we just have our properties string
	//Lets run our regular expression over them
	//string should be property=value,property=value
	//got this regexp from http://stackoverflow.com/questions/168171/regular-expression-for-parsing-name-value-pairs
	$regexpression='/([^=,]*)=("[^"]*"|[^,"]*)/';
	$matches; 	

	//here we match the filter string and split into name array (matches[1]) and value array (matches[2])
	//we then add those to a name value array.
	$itemprops = array();
	if (preg_match_all($regexpression, $rawproperties,$matches,PREG_PATTERN_ORDER)){		
		$propscount = count($matches[1]);
		for ($cnt =0; $cnt < $propscount; $cnt++){
			// echo $matches[1][$cnt] . "=" . $matches[2][$cnt] . " ";
			$itemprops[$matches[1][$cnt]]=$matches[2][$cnt];
		}
	}

	return $itemprops;

}

function fetchAutoWidgetCode($widget,$paramsArray,$width,$height, $bgcolor="#FFFFFF"){
	global $CFG, $PAGE;
	$ret="";
	 $browser = new Browser();
	 switch($browser->getBrowser()){
		case Browser::BROWSER_IPAD:
		case Browser::BROWSER_IPOD:
		case Browser::BROWSER_IPHONE:
		case Browser::BROWSER_ANDROID:
			
			$pos =strPos($widget,".lzx.");
			if ($pos > 0){
					$basestring = substr($widget,0,$pos+4);
					$widget=$basestring . ".js";
					$ret= fetchJSWidgetCode($widget,$paramsArray,$width,$height, $bgcolor="#FFFFFF");	
			}
			break;
		default:
			//$ret=$browser->getPlatform();
			$ret = fetchSWFWidgetCode($widget,$paramsArray,$width,$height, $bgcolor="#FFFFFF");	
	 }
	 return $ret;
}

function fetchSWFWidgetCode($widget,$paramsArray,$width,$height, $bgcolor="#FFFFFF"){
	global $CFG, $PAGE;
	
	//build the parameter string out of the passed in array
	$params="?";
	foreach ($paramsArray as $key => $value) {
    	$params .= '&' . $key . '=' . $value;
	}
	
	//add in any common params
	$params .= '&debug=false&lzproxied=false'; 
	
	//if we wish to pass in more common params, here is the place
	//eg. $params .= '&modulename=' . $PAGE->cm->modname;
	
	$retcode = "
        <table><tr><td>
        <script type=\'text/javascript\'>
            lzOptions = { ServerRoot: \'\'};
        </script>
        <script type=\"text/javascript\" src=\"{$CFG->wwwroot}/filter/poodll/flash/embed-compressed.js\"></script>
        <script type=\"text/javascript\">
" . '	lz.embed.swf({url: \'' . $CFG->wwwroot . '/filter/poodll/flash/' . $widget . $params . 
		 '\', bgcolor: \'' . $bgcolor . '\', allowfullscreen: \'true\', width: \'' .$width . '\', height: \'' . $height . '\', id: \'lzapp_' . rand(100000, 999999) . '\', accessible: \'false\'});	
		
' . "
        </script>
        <noscript>
            Please enable JavaScript in order to use this application.
        </noscript>
        </td></tr>
		</table>";
		
		return $retcode;


}

function fetchJSWidgetCode($widget,$paramsArray,$width,$height, $bgcolor="#FFFFFF", $usemastersprite="false"){
	global $CFG, $PAGE;

	//build the parameter string out of the passed in array
	$params="?";
	foreach ($paramsArray as $key => $value) {
    	$params .= '&' . $key . '=' . $value;
	}
	
	//add in any common params
	$params .= '&debug=false&lzproxied=false';	
	
	//path to our js idgets folder
	$pathtoJS = $CFG->wwwroot . '/filter/poodll/js/';
	$pathtowidgetfolder = $CFG->wwwroot . '/filter/poodll/js/' . $widget . '/';
	
	
	$retframe="<iframe scrolling=\"no\" frameBorder=\"0\" src=\"{$pathtoJS}poodlliframe.php?widget={$widget}&paramstring=" . urlencode($params) . "&width={$width}&height={$height}&bgcolor={$bgcolor}&usemastersprite={$usemastersprite}\" width=\"{$width}\" height=\"{$height}\"></iframe>"; 
	return $retframe;


}
