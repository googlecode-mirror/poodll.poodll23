<?php

// This file is used to concatenate some of the filter settings
// This could probably be done elsewhere more efficiently,
// however being a rookie php guy I couldn't think of where.

// Formerly the configuration file for the early alpha
// versions of poodll.  Now most settings are stored in
// the CFG under the filter for poodll.

global $CFG;



// Setting up the PoodLL Media Server String
if($CFG->filter_poodll_serverport=='443'){
	$protocol='rtmpt';
}else{
	$protocol='rtmp';
}

$CFG->poodll_media_server = $protocol . '://' . $CFG->filter_poodll_servername . ':' . $CFG->filter_poodll_serverport   . '/' . $CFG->filter_poodll_serverid;



// Setting up the PoodLL Media Server String ... added serverport Justin 20110827
//$CFG->poodll_media_server = 'rtmp://' . $CFG->filter_poodll_servername . ':' .  $CFG->filter_poodll_serverport  . '/' . $CFG->filter_poodll_serverid;

// A short string to the PoodLL media server (useful in code but never used anywhere)
// $CFG->poodll_media_rtmp = CFG->filter_poodll_servername . "/" . CFG->filter_poodll_serverid;
// OBSOLETE - DO NOT UNCOMMENT ANYTHING BELOW THIS LINE!
//VideoParams
//$CFG->mediaplayerserver = 'rtmp://poodll.com/poodll';
//$CFG->rtmp = 'poodll.com/poodll';
//$CFG->mediaplayerheight = 300;
//$CFG->mediaplayerwidth  = 400;
//$CFG->videocaptureheight = 240;
//$CFG->videocapturewidth  = 320;
//$CFG->audioplayerheight = 25;
//$CFG->audioplayerwidth  = 450;
//$CFG->talkbackplayerheight = 300;
//$CFG->talkbackplayerwidth  = 760;
//$CFG->mediaplayerbuffer = 0;
//$CFG->mediaplayerfile = 'myfile.flv';
//$CFG->mediaplayerrepeat=false;
//$CFG->mediaplayerallowfullscreen=false;
//$CFG->mediaplayerautostart=false;
//$CFG->rtmphost = 'poodll.com';	
//$CFG->rtmpapp = 'poodll';	
//$CFG->rtmpport = '1935';			
//$CFG->rtmppath = '/var/lib/moodle';	
//$CFG->useproxy = 'false';
//$CFG->proxyip = '192.168.16.1:8080';
//$CFG->proxyport = '8080';
//$CFG->overwritemediafile = 'true';
//$CFG->usecourseid = 'true';
//$CFG->broadcastwidth = '800';
//$CFG->broadcastheight = '600';
//$CFG->capturedevice = 'VHScrCap';
//$CFG->capturesizeindex = '4';
//myjournal_media_params
//$CFG->myjournal_showAVRecorder = true;
//$CFG->myjournal_recordAudioOnly = true;
//$CFG->myjournal_studentsCanRecord = true;

?>
