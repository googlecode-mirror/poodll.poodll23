<?php
      
/**
* @author Justin Hunt
* @param        int            course id
* @param        string         text to be filtered
*/
 //Justin 20081224
//Get our library for handling media
require_once($CFG->libdir . '/poodllresourcelib.php');




function poodll_filter($courseid, $text) {
    global $CFG;
       
    if (!is_string($text)) {
        // non string data can not be filtered anyway
        return $text;
    }
    
    $newtext = $text; // fullclone is slow and not needed here
    
    $search = '/{POODLL:.*?}/is';
    $newtext = preg_replace_callback($search, 'poodll_callback', $newtext);
    
    if (is_null($newtext) or $newtext === $text) {
        // error or not filtered
        return $text;
    }

    return $newtext;
}

function poodll_callback($link) {
    global $CFG, $COURSE, $USER;
	//get our filter props
	//we use a function in the poodll poodllresourcelib, because
	//parsing will also need to be done by the html editor
	$filterprops=	fetch_filter_properties($link[0]);

	//if we have no props, quit
	if(empty($filterprops)){return "";}
	
	//if we want to ignore the filter (for "how to use a filter" demos) we let it go
	//to use this, make the last parameter of the filter passthrough=1
	if (!empty($filterprops['passthrough'])) return str_replace( ",passthrough=1","",$link[0]);
	
	//Init our return variable 
	$returnHtml ="";

	//depending on the type of filter
	switch ($filterprops['type']){
		case 'video': 
			//$returnHtml="<BR />" . fetchSimpleVideoPlayer($filterprops['path'],$filterprops['width'],$filterprops['height']);
			$returnHtml="<BR />" . fetchSimpleVideoPlayer($filterprops['path'],!empty($filterprops['width']) ? $filterprops['width'] : $CFG->filter_poodll_videowidth,!empty($filterprops['height']) ? $filterprops['height'] :  $CFG->filter_poodll_videoheight,!empty($filterprops['protocol']) ? $filterprops['protocol'] : 'rtmp',!empty($filterprops['embed']) ? $filterprops['embed']=='true' : false,!empty($filterprops['permitfullscreen']) ? $filterprops['permitfullscreen'] : false ,!empty($filterprops['embedstring']) ? $filterprops['embedstring'] : 'Play');
			break;
		
		case 'wmvvideo': 
			$returnHtml="<BR />" . fetchWMVPlayer($filterprops['path'],!empty($filterprops['width']) ? $filterprops['width'] : $CFG->filter_poodll_videowidth,!empty($filterprops['height']) ? $filterprops['height'] :  $CFG->filter_poodll_videoheight);
			break;
			
		case 'audio':
			$returnHtml="<BR />" . fetchSimpleAudioPlayer($filterprops['path'],!empty($filterprops['protocol']) ? $filterprops['protocol'] : 'rtmp',!empty($filterprops['width']) ? $filterprops['width'] : $CFG->filter_poodll_audiowidth,!empty($filterprops['height']) ? $filterprops['height'] :  $CFG->filter_poodll_audioheight,!empty($filterprops['embed']) ? $filterprops['embed']=='true' : false,!empty($filterprops['embedstring']) ? $filterprops['embedstring'] : 'Play');
			break;
			
		case 'audiolist':
			$returnHtml="<BR />" . fetchAudioListPlayer($filterprops['path'],!empty($filterprops['filearea']) ? $filterprops['filearea'] : 'content',!empty($filterprops['protocol']) ? $filterprops['protocol'] : 'rtmp',!empty($filterprops['width']) ? $filterprops['width'] : 400,!empty($filterprops['height']) ? $filterprops['height'] : 250, !empty($filterprops['sequentialplay']) ? $filterprops['sequentialplay'] : 'true');
			break;
			
		case 'audiotest':
			$returnHtml="<BR />" . fetchAudioTestPlayer($filterprops['path'],!empty($filterprops['protocol']) ? $filterprops['protocol'] : 'rtmp',!empty($filterprops['width']) ? $filterprops['width'] : 400,!empty($filterprops['height']) ? $filterprops['height'] : 50);
			break;	
			
		case 'talkback':
			$returnHtml="<BR />" . fetchTalkbackPlayer($filterprops['path'],!empty($filterprops['protocol']) ? $filterprops['protocol'] : 'rtmp',!empty($filterprops['recordable']) ? $filterprops['recordable'] : 'false',!empty($filterprops['savefolder']) ? $filterprops['savefolder'] : 'default');
			break;
			
		case 'bigvideogallery':
			$returnHtml="<BR />" . fetchBigVideoGallery($filterprops['path'],!empty($filterprops['protocol']) ? $filterprops['protocol'] : 'rtmp',!empty($filterprops['width']) ? $filterprops['width'] : $CFG->filter_poodll_biggallwidth,!empty($filterprops['height']) ? $filterprops['height'] :  $CFG->filter_poodll_biggallheight);
			break;	
			
		case 'videorecorder':
			$returnHtml="<BR />" . fetchSimpleVideoRecorder($filterprops['savefolder']);
			break;	
			
		case 'audiorecorder':
			$returnHtml="<BR />" . fetchSimpleAudioRecorder($filterprops['savefolder']);
			break;

		case 'calculator':
			$returnHtml="<BR />" . fetch_poodllcalc(!empty($filterprops['width']) ? $filterprops['width'] : 300,
				!empty($filterprops['height']) ? $filterprops['height'] : 400);
			break;

		case 'teachersrecorder':
			$returnHtml="<BR />" . fetch_teachersrecorder($filterprops['savepath'], "");
			break;	
			
		case 'adminconsole':
			$returnHtml="<BR />" . fetch_poodllconsole("","billybob",-1,true);
			break;	

		case 'countdown':
			$returnHtml="<BR />" . fetch_countdowntimer($filterprops['initseconds'],
				!empty($filterprops['usepresets']) ? $filterprops['usepresets'] : 'false',
				!empty($filterprops['width']) ? $filterprops['width'] : 400,
				!empty($filterprops['height']) ? $filterprops['height'] : 265,
				!empty($filterprops['fontsize']) ? $filterprops['fontsize'] : 64,
				!empty($filterprops['mode']) ? $filterprops['mode'] : 'normal',
				!empty($filterprops['permitfullscreen']) ? $filterprops['permitfullscreen'] : false, 
				!empty($filterprops['uniquename']) ? $filterprops['uniquename'] : 'auniquename');
			break;
		
		case 'counter':
			$returnHtml="<BR />" . fetch_counter(!empty($filterprops['initcount']) ? $filterprops['initcount']  : 0,
				!empty($filterprops['usepresets']) ? $filterprops['usepresets'] : 'false',
				!empty($filterprops['width']) ? $filterprops['width'] : 480,
				!empty($filterprops['height']) ? $filterprops['height'] : 265,
				!empty($filterprops['fontsize']) ? $filterprops['fontsize'] : 64,
				!empty($filterprops['permitfullscreen']) ? $filterprops['permitfullscreen'] : false );
			break;	
		
		case 'dice':
			$returnHtml="<BR />" . fetch_dice(!empty($filterprops['dicecount']) ? $filterprops['dicecount']  : 1,
				!empty($filterprops['dicesize']) ? $filterprops['dicesize'] : 200,
				!empty($filterprops['width']) ? $filterprops['width'] : 300,
				!empty($filterprops['height']) ? $filterprops['height'] : 300);
			break;
			
		case 'flashcards':
			$returnHtml="<BR />" . fetch_flashcards($filterprops['cardset'],
				!empty($filterprops['cardwidth']) ? $filterprops['cardwidth'] : 300,
				!empty($filterprops['cardheight']) ? $filterprops['cardheight'] : 150,
				!empty($filterprops['randomize']) ? $filterprops['randomize'] : 'yes',
				!empty($filterprops['width']) ? $filterprops['width'] : 400,
				!empty($filterprops['height']) ? $filterprops['height'] : 300);
			break;
			
		case 'stopwatch':
			$returnHtml="<BR />" . fetch_stopwatch(!empty($filterprops['width']) ? $filterprops['width'] : 400,
				!empty($filterprops['height']) ? $filterprops['height'] : 265,!empty($filterprops['fontsize']) ? $filterprops['fontsize'] : 64,
				!empty($filterprops['mode']) ? $filterprops['mode'] : 'normal',
				!empty($filterprops['permitfullscreen']) ? $filterprops['permitfullscreen'] : false, 
				!empty($filterprops['uniquename']) ? $filterprops['uniquename'] : 'auniquename');
			break;
						
		case 'smallvideogallery':
			$returnHtml="<BR />" . fetchSmallVideoGallery($filterprops['path'],!empty($filterprops['protocol']) ? $filterprops['protocol'] : 'rtmp',
				!empty($filterprops['width']) ? $filterprops['width'] : $CFG->filter_poodll_smallgallwidth,
				!empty($filterprops['height']) ? $filterprops['height'] :  $CFG->filter_poodll_smallgallheight,
				!empty($filterprops['permitfullscreen']) ? $filterprops['permitfullscreen'] : false );
			break;	
			
		case 'newpoodllpairwork':
			$returnHtml="<BR />" . fetch_embeddablepairclient(!empty($filterprops['width']) ? $filterprops['width'] : $CFG->filter_poodll_newpairwidth,
				!empty($filterprops['height']) ? $filterprops['height'] : $CFG->filter_poodll_newpairheight,
				!empty($filterprops['chat']) ? $filterprops['chat'] : true,
				!empty($filterprops['whiteboard']) ? $filterprops['whiteboard'] : false, 
				!empty($filterprops['showvideo']) ? $filterprops['showvideo'] : false,
				!empty($filterprops['whiteboardback']) ? $filterprops['whiteboardback'] : ''
				);
			break;	

		case 'screensubscribe':
			$returnHtml="<BR />" . fetch_screencast_subscribe("",true,!empty($filterprops['width']) ? $filterprops['width'] : $CFG->filter_poodll_showwidth,
				!empty($filterprops['height']) ? $filterprops['height'] : $CFG->filter_poodll_showheight
				);
			break;	

		case 'poodllpalette':
			$returnHtml="<BR />" . fetch_poodllpalette($filterprops['width'],$filterprops['height'],"swf");
			break;	
			
		case 'whiteboard':
			$returnHtml="<BR />" . fetch_whiteboard(!empty($filterprops['boardname']) ? $filterprops['boardname'] : "whiteboard",
				!empty($filterprops['backimage']) ? $filterprops['backimage'] : "",
				(!empty($filterprops['slave'])&& $filterprops['slave']=='true') ? $filterprops['slave'] : false,
				!empty($filterprops['rooms']) ? $filterprops['rooms'] : "",
				!empty($filterprops['width']) ? $filterprops['width'] :  $CFG->filter_poodll_whiteboardwidth,
				!empty($filterprops['height']) ? $filterprops['height'] :  $CFG->filter_poodll_whiteboardheight,
				!empty($filterprops['mode']) ? $filterprops['mode'] :  'normal',
				(!empty($filterprops['standalone'])&& $filterprops['standalone']=='true')  ? $filterprops['standalone'] :  'false'
				);
			break;									

		case 'poodllpairwork':
			$courseid = $COURSE->id;
			$username = $USER->username;
			
			$poodllpairworkplayer ="";
			$studentalias="";
			$pairmap="";
			
			if ($pairmap = get_record("poodllpairwork_usermap", "username", $username, "course", $courseid)) {
				$studentalias = $pairmap->role;
			}				
					
			//if we have a role and hence a session.
			if ($studentalias != ""){			
			 	$me = get_record('user', 'username', $username);
			 	$partner = get_record('user', 'username', $pairmap->partnername);
			 	$partnerpic = fetch_user_picture($partner,35);
			 	$mepic = fetch_user_picture($me,35);
			 	$poodllpairworkplayer =  "<h4>" . get_string("yourpartneris", "poodllpairwork") . fullname($partner) . "</h4>";
			 	$poodllpairworkplayer .= fetchPairworkPlayer($pairmap->username,$pairmap->partnername,$mepic, fullname($me),$partnerpic,fullname($partner));					
		
			}
			
			$returnHtml="<BR />" . $poodllpairworkplayer;
			break;



		default:




	
	}

	//return our html
	return $returnHtml;

}
?>


