<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the definition for the library class for onlinepoodll submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package    assignsubmission_onlinepoodll
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
//Get our poodll resource handling lib
require_once($CFG->dirroot . '/filter/poodll/poodllresourcelib.php');

defined('MOODLE_INTERNAL') || die();
/**
 * File area for online text submission assignment
 */
define('ASSIGNSUBMISSION_ONLINEPOODLL_FILEAREA', 'submissions_onlinepoodll');

//some constants for the type of online poodll assignment
define('OM_REPLYMP3VOICE',0);
define('OM_REPLYVOICE',1);
define('OM_REPLYVIDEO',2);
define('OM_REPLYWHITEBOARD',3);
define('OM_REPLYSNAPSHOT',4);
define('OM_REPLYTALKBACK',5);


define('FILENAMECONTROL','onlinepoodll');



/**
 * library class for onlinepoodll submission plugin extending submission plugin base class
 *
 * @package    assignsubmission_onlinepoodll
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_onlinepoodll extends assign_submission_plugin {

    /**
     * Get the name of the online text submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('onlinepoodll', 'assignsubmission_onlinepoodll');
    }

	    /**
     * Get the default setting for file submission plugin
     *
     * @global stdClass $CFG
     * @global stdClass $COURSE
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        $recordertype = $this->get_config('recordertype');
      

        $recorderoptions = array( OM_REPLYMP3VOICE => get_string("replymp3voice", "assignsubmission_onlinepoodll"), 
				OM_REPLYVOICE => get_string("replyvoice", "assignsubmission_onlinepoodll"), 
				OM_REPLYVIDEO => get_string("replyvideo", "assignsubmission_onlinepoodll"),
				OM_REPLYWHITEBOARD => get_string("replywhiteboard", "assignsubmission_onlinepoodll"),
				OM_REPLYSNAPSHOT => get_string("replysnapshot", "assignsubmission_onlinepoodll"));
		
		//we don't support talkback yet
		//OM_REPLYTALKBACK => get_string("replytalkback", "assignsubmission_onlinepoodll"));
        
		$mform->addElement('select', 'assignsubmission_onlinepoodll_recordertype', get_string("recordertype", "assignsubmission_onlinepoodll"), $recorderoptions);
        $mform->addHelpButton('assignsubmission_onlinepoodll_recordertype', 'defaultname', 'assignsubmission_onlinepoodll');
        $mform->setDefault('assignsubmission_onlinepoodll_recordertype', $recordertype);
      //  $mform->disabledIf('assignsubmission_onlinepoodll_recordertype', 'assignsubmission_onlinepoodll_enabled', 'eq', 0);


    }
    
    /**
     * Save the settings for file submission plugin
     *
     * @param stdClass $data
     * @return bool 
     */
    public function save_settings(stdClass $data) {
        $this->set_config('recordertype', $data->assignsubmission_onlinepoodll_recordertype);
        return true;
    }

   /**
    * Get onlinepoodll submission information from the database
    *
    * @param  int $submissionid
    * @return mixed
    */
    private function get_onlinepoodll_submission($submissionid) {
        global $DB;

        return $DB->get_record('assignsubmission_onlinepood', array('submission'=>$submissionid));
    }

    /**
     * Add form elements for settings
     *
     * @param mixed $submission can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
		 global $CFG, $USER;
		 
        $elements = array();

        $submissionid = $submission ? $submission->id : 0;

        if ($submission) {
            $onlinepoodllsubmission = $this->get_onlinepoodll_submission($submission->id);
        }

		//We prepare our form here and fetch/save data in SAVE method
		$usercontextid=get_context_instance(CONTEXT_USER, $USER->id)->id;
		$draftitemid = file_get_submitted_draft_itemid(FILENAMECONTROL);
		$contextid=$this->assignment->get_context()->id;
		file_prepare_draft_area($draftitemid, $contextid, 'assignsubmission_onlinepoodll', ASSIGNSUBMISSION_ONLINEPOODLL_FILEAREA, $submissionid, null,null);
		$mform->addElement('hidden', 'draftitemid', $draftitemid);
		$mform->addElement('hidden', 'usercontextid', $usercontextid);	
		$mform->addElement('hidden', FILENAMECONTROL, '',array('id' => FILENAMECONTROL));
				

		
		
		//Do we need audio or text? or both?
		//the customdata is info we passed in up around line 175 in the view method.
		switch($this->get_config('recordertype')){
			
			case OM_REPLYVOICE:
				//$mediadata= fetchSimpleAudioRecorder('onlinemedia' . $this->_customdata['cm']->id , $USER->id);
				//$mediadata= fetchSimpleAudioRecorder('assignment/' . $this->_customdata['assignment']->id , $USER->id);
				$mediadata= fetchAudioRecorderForSubmission('swf','onlinepoodll',FILENAMECONTROL, $usercontextid ,'user','draft',$draftitemid);
				$mform->addElement('static', 'description', '',$mediadata);

				break;
				
			case OM_REPLYMP3VOICE:
				//$mediadata= fetchSimpleAudioRecorder('onlinemedia' . $this->_customdata['cm']->id , $USER->id);
				//$mediadata= fetchSimpleAudioRecorder('assignment/' . $this->_customdata['assignment']->id , $USER->id);
				$mediadata= fetchMP3RecorderForSubmission(FILENAMECONTROL, $usercontextid ,'user','draft',$draftitemid,640,400);
				$mform->addElement('static', 'description', '',$mediadata);
				break;
				
			case OM_REPLYWHITEBOARD:
				//$mediadata= fetchSimpleAudioRecorder('onlinemedia' . $this->_customdata['cm']->id , $USER->id);
				//$mediadata= fetchSimpleAudioRecorder('assignment/' . $this->_customdata['assignment']->id , $USER->id);
				$mediadata= fetchWhiteboardForSubmission(FILENAMECONTROL, $usercontextid ,'user','draft',$draftitemid);
				$mform->addElement('static', 'description', '',$mediadata);
				break;
			
			case OM_REPLYSNAPSHOT:
				//$mediadata= fetchSimpleAudioRecorder('onlinemedia' . $this->_customdata['cm']->id , $USER->id);
				//$mediadata= fetchSimpleAudioRecorder('assignment/' . $this->_customdata['assignment']->id , $USER->id);
				$mediadata= fetchSnapshotCameraForSubmission(FILENAMECONTROL,"snap.jpg" ,350,400,$usercontextid ,'user','draft',$draftitemid);
				$mform->addElement('static', 'description', '',$mediadata);
				break;

			case OM_REPLYVIDEO:
				
			
				$mediadata= fetchVideoRecorderForSubmission('swf','onlinepoodll',FILENAMECONTROL, $usercontextid ,'user','draft',$draftitemid);
				$mform->addElement('static', 'description', '',$mediadata);			
									
				break;
					
		}

        // hidden params
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
		return true;

    }

	
	
	/*
	* Fetch the player to show the submitted recording(s)
	*
	*
	*
	*/
	function fetchResponses($submissionid, $checkfordata=false, $embed=false){
		global $CFG;
		
		$responsestring = "";
		
		//if we are showing a list of files we want to use text links not players
		//a whole page of players can crash a browser.
		
		//modify Justin 20120525 lists of flowplayers/jw players will break if embedded and 
		// flowplayers should have splash screens to defer loading anyway
		if($CFG->filter_poodll_defaultplayer == 'pd' && $embed == 'true'){
			$embed = 'true';
			$embedstring = get_string('clicktoplay', 'assignment_poodllonline');
		}else{
			$embedstring = 'clicktoplay';
			$embed='false';
		}
		
		
		//get filename, from the filearea for this submission. 
		//there should be only one.
		$fs = get_file_storage();
		$filename="";
        $files = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_onlinepoodll', ASSIGNSUBMISSION_ONLINEPOODLL_FILEAREA, $submissionid, "id", false);
        if (!empty($files)) {
            foreach ($files as $file) {
                $filename = $file->get_filename();
				break;
			}
		}
		
		//if this is a playback area, for teacher, show a string if no file
		if ($checkfordata  && empty($filename)){ 
					$responsestring .= "No submission found";
		}else{	
			//The path to any media file we should play
			$mediapath = $CFG->wwwroot.'/pluginfile.php/'.$this->assignment->get_context()->id.'/assignsubmission_onlinepoodll/' . ASSIGNSUBMISSION_ONLINEPOODLL_FILEAREA  . '/'.$submissionid.'/'.$filename;
			$mediapath = urlencode($mediapath);
		
			//prepare our response string, which will parsed and replaced with the necessary player
			switch($this->get_config('recordertype')){
							
				case OM_REPLYVOICE:
				case OM_REPLYMP3VOICE:
				case OM_REPLYTALKBACK:
						$responsestring .= format_text('{POODLL:type=audio,path='.	$mediapath .',protocol=http,embed=' . $embed . ',embedstring='. $embedstring .'}', FORMAT_HTML);
						break;						
					
				case OM_REPLYVIDEO:
						$responsestring .= format_text('{POODLL:type=video,path='.	$mediapath .',protocol=http,embed=' . $embed . ',embedstring='. $embedstring .'}', FORMAT_HTML);
						break;

				case OM_REPLYWHITEBOARD:
					$responsestring .= "<img alt=\"submittedimage\" width=\"" . $CFG->filter_poodll_videowidth . "\" src=\"" . urldecode($mediapath) . "\" />";
					break;
					
				case OM_REPLYSNAPSHOT:
					$responsestring .= "<img alt=\"submittedimage\" width=\"" . $CFG->filter_poodll_videowidth . "\" src=\"" . urldecode($mediapath) . "\" />";
					break;
					
				default:
					$responsestring .= format_text('{POODLL:type=audio,path='.	$mediapath .',protocol=http,embed=' . $embed . ',embedstring='. $embedstring .'}', FORMAT_HTML);
					break;	
				
			}//end of switch
		}//end of if (checkfordata ...) 
		
		
		return $responsestring;
		
	}//end of fetchResponses
	
	
	
     /**
      * Save data to the database
      *
      * @param stdClass $submission
      * @param stdClass $data
      * @return bool
      */
     public function save(stdClass $submission, stdClass $data) {
        global $DB;


		//Move recorded files from draft to the correct area
		$this->shift_draft_file($submission);

        $onlinepoodllsubmission = $this->get_onlinepoodll_submission($submission->id);
        if ($onlinepoodllsubmission) {
            return $DB->update_record('assignsubmission_onlinepood', $onlinepoodllsubmission);
        } else {

            $onlinepoodllsubmission = new stdClass();

            $onlinepoodllsubmission->submission = $submission->id;
            $onlinepoodllsubmission->assignment = $this->assignment->get_instance()->id;
            $onlinepoodllsubmission->recorder = $this->get_config('recordertype');
            return $DB->insert_record('assignsubmission_onlinepood', $onlinepoodllsubmission) > 0;
        }


    }
    
    
    function shift_draft_file($submission) {
        global $CFG, $USER, $DB,$COURSE;


		
 
		//When we add the recorder via the poodll filter, it adds a hidden form field of the name FILENAMECONTROL
		//the recorder updates that field with the filename of the audio/video it recorded. We pick up that filename here.

		$filename = optional_param(FILENAMECONTROL, '', PARAM_RAW);
		$draftitemid = optional_param('draftitemid', '', PARAM_RAW);
		$usercontextid = optional_param('usercontextid', '', PARAM_RAW);
		 $fs = get_file_storage();
		 $browser = get_file_browser();
         $fs->delete_area_files($this->assignment->get_context()->id, 'assignsubmission_onlinepoodll',ASSIGNSUBMISSION_ONLINEPOODLL_FILEAREA , $submission->id);

		
		//fetch the file info object for our original file
		$original_context = get_context_instance_by_id($usercontextid);
		$draft_fileinfo = $browser->get_file_info($original_context, 'user','draft', $draftitemid, '/', $filename);
	
		//perform the copy	
		if($draft_fileinfo){
			
			//create the file record for our new file
			$file_record = array(
			'userid' => $USER->id,
			'contextid'=>$this->assignment->get_context()->id, 
			'component'=>'assignsubmission_onlinepoodll', 
			'filearea'=>ASSIGNSUBMISSION_ONLINEPOODLL_FILEAREA ,
			'itemid'=>$submission->id, 
			'filepath'=>'/', 
			'filename'=>$filename,
			'author'=>'moodle user',
			'license'=>'allrighttsreserved',		
			'timecreated'=>time(), 
			'timemodified'=>time()
			);
			$ret = $draft_fileinfo->copy_to_storage($file_record);
			
		}//end of if $draft_fileinfo

	}//end of shift_draft_file
    



    /**
     * Display the list of files  in the submission status table
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set this to true if the list of files is long
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
    	$showviewlink = false;

		//our response, this will output a player/image
		return $this->fetchResponses($submission->id);

		//the default return method, this just produces a link.
      // return $this->assignment->render_area_files('assignsubmission_onlinepoodll', ASSIGNSUBMISSION_ONLINEPOODLL_FILEAREA, $submission->id);
    }

      /**
     * Produce a list of files suitable for export that represent this feedback or submission
     *
     * @param stdClass $submission The submission
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission) {
        $result = array();
        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_onlinepoodll', ASSIGNSUBMISSION_ONLINEPOODLL_FILEAREA, $submission->id, "timemodified", false);

        foreach ($files as $file) {
            $result[$file->get_filename()] = $file;
        }
        return $result;
    }

    /**
     * Display the saved text content from the editor in the view table
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        $result = '';

        $onlinepoodllsubmission = $this->get_onlinepoodll_submission($submission->id);


        if ($onlinepoodllsubmission) {


            // show our responses in a player
			$result = $this->fetchResponses($submission->id);
		
			//the default render method. Only shows a link
			// return $this->assignment->render_area_files('assignsubmission_onlinepoodll', ASSIGN_FILEAREA_SUBMISSION_ONLINEPOODLL, $submission->id);

        }

        return $result;
    }
	

    

     /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        if ($type == 'poodllonline' && $version >= 2011112900) {
            return true;
        }
        return false;
    }


    /**
     * Upgrade the settings from the old assignment to the new plugin based one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment - the database for the old assignment instance
     * @param string $log record log events here
     * @return bool Was it a success?
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
		switch($oldassignment->var3){
			case 0:
			case 1:
			case 2:
				$this->set_config('recordertype',  OM_REPLYVOICE);
				break;
			case 7:
				$this->set_config('recordertype',  OM_REPLYMP3VOICE);
				break;
			case 3:
			case 4:
				$this->set_config('recordertype',  OM_REPLYVIDEO);
				break;
			case 5:
				$this->set_config('recordertype',  OM_REPLYTALKBACK);
				break;
			case 6:
				$this->set_config('recordertype',  OM_REPLYWHITEBOARD);
				break;
		}

        return true;
    }

    /**
     * Upgrade the submission from the old assignment to the new one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment The data record for the old assignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $submission The data record for the new submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmission, stdClass $submission, & $log) {
        global $DB;

        $onlinepoodllsubmission = new stdClass();

        $onlinepoodllsubmission->submission = $submission->id;
        $onlinepoodllsubmission->assignment = $this->assignment->get_instance()->id;

    

        if (!$DB->insert_record('assignsubmission_onlinepood', $onlinepoodllsubmission) > 0) {
            $log .= get_string('couldnotconvertsubmission', 'mod_assign', $submission->userid);
            return false;
        }

        // now copy the area files
        $this->assignment->copy_area_files_for_upgrade($oldcontext->id,
                                                        'mod_assignment',
                                                        'submission',
                                                        $oldsubmission->id,
                                                        // New file area
                                                        $this->assignment->get_context()->id,
                                                        'assignsubmission_onlinepoodll',
                                                        ASSIGNSUBMISSION_ONLINEPOODLL_FILEAREA,
                                                        $submission->id);
        return true;
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $submission The new submission
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // format the info for each submission plugin add_to_log
      //  $onlinepoodllsubmission = $this->get_onlinepoodll_submission($submission->id);
        $onlinepoodllloginfo = '';

        $onlinepoodllloginfo .= "submission id:" . $submission->id . " added.";

        return $onlinepoodllloginfo;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // will throw exception on failure
        $DB->delete_records('assignsubmission_onlinepood', array('assignment'=>$this->assignment->get_instance()->id));

        return true;
    }

    /**
     * No text is set for this plugin
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        return $this->view($submission) == '';
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(ASSIGNSUBMISSION_ONLINEPOODLL_FILEAREA=>$this->get_name());
    }

}


