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
 * repository_poodllplus class
 * This is a subclass of repository class
 *
 * @package    repository_poodllplus
 * @category   repository
 * @copyright  2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
//Get our poodll resource handling lib
require_once($CFG->dirroot . '/filter/poodll/poodllresourcelib.php');
 
class repository_poodllplus extends repository {


    /*
     * Begin of File picker API implementation
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        global $action, $itemid;
        parent::__construct ($repositoryid, $context, $options);
     
    }
    
	
	 public function check_login() {
        return !empty($this->keyword);
    }

	
    public static function get_instance_option_names() {
    	return array('repo_type');
    }
    
    public function instance_config_form($mform) {
        $repo_type_options = array(
        	get_string('mp3recorder', 'repository_poodllplus'),
        	get_string('snapshottaker', 'repository_poodllplus'),
			get_string('drawpad', 'repository_poodllplus')
        );
        
        $mform->addElement('select', 'repo_type', get_string('repo_type', 'repository_poodllplus'), $repo_type_options);
        
        $mform->addRule('repo_type', get_string('required'), 'required', null, 'client');
		
    }
	


 
/**
     * Process uploaded file
     * @return array|bool
     */
    public function upload($search_text) {
        global $USER, $CFG;

        $record = new stdClass();
        $record->filearea = 'draft';
        $record->component = 'user';
        $record->filepath = optional_param('savepath', '/', PARAM_PATH);
        $record->itemid   = optional_param('itemid', 0, PARAM_INT);
        $record->license  = optional_param('license', $CFG->sitedefaultlicense, PARAM_TEXT);
        $record->author   = optional_param('author', '', PARAM_TEXT);

        $context = get_context_instance(CONTEXT_USER, $USER->id);
        $filename = required_param('upload_filename', PARAM_FILE);
        $filedata = required_param('upload_filedata', PARAM_RAW);
        $filedata = base64_decode($filedata);

        $fs = get_file_storage();
        $sm = get_string_manager();

        if ($record->filepath !== '/') {
            $record->filepath = file_correct_filepath($record->filepath);
        }

        $record->filename = $filename;
        
        if (empty($record->itemid)) {
            $record->itemid = 0;
        }

        $record->contextid = $context->id;
        $record->userid    = $USER->id;
        $record->source    = '';

        if (repository::draftfile_exists($record->itemid, $record->filepath, $record->filename)) {
            $existingfilename = $record->filename;
            $unused_filename = repository::get_unused_filename($record->itemid, $record->filepath, $record->filename);
            $record->filename = $unused_filename;
            $stored_file = $fs->create_file_from_string($record, $filedata);
            $event = array();
            $event['event'] = 'fileexists';
            $event['newfile'] = new stdClass;
            $event['newfile']->filepath = $record->filepath;
            $event['newfile']->filename = $unused_filename;
            $event['newfile']->url = moodle_url::make_draftfile_url($record->itemid, $record->filepath, $unused_filename)->out();

            $event['existingfile'] = new stdClass;
            $event['existingfile']->filepath = $record->filepath;
            $event['existingfile']->filename = $existingfilename;
            $event['existingfile']->url      = moodle_url::make_draftfile_url($record->itemid, $record->filepath, $existingfilename)->out();;
            return $event;
        } else {
            $stored_file = $fs->create_file_from_string($record, $filedata);

                
            return array(
                'url'=>moodle_url::make_draftfile_url($record->itemid, $record->filepath, $record->filename)->out(),
                'id'=>$record->itemid,
                'file'=>$record->filename);
        }
    }

    /**
     * Generate upload form
     */
    public function print_login($ajax = true) {
		switch ($this->options['repo_type']){
			case 1: $widget = $this->fetch_snapshottaker();break;
			case 2: $widget = $this->fetch_drawpad();break;
			default: $widget = $this->fetch_mp3recorder();break;
		}
		
        $ret = array();
        $ret['upload'] = array('label'=>$widget, 'id'=>'repo-form');
        return $ret;
    }
	
	public function fetch_mp3recorder(){
		 global $CFG;
       //initialize our return string
	   $recorder = "";
       
	   //set up params for mp3 recorder
	   $url=$CFG->wwwroot.'/repository/poodllplus/flash/recorder.swf?gateway=form';
		$callback = urlencode("(function(a, b){d=document;d.g=d.getElementById;fn=d.g('upload_filename');fn.value=a;fd=d.g('upload_filedata');fd.value=b;f=fn;while(f.tagName!='FORM')f=f.parentNode;f.repo_upload_file.type='text';f.repo_upload_file.value='bogus.mp3';f.nextSibling.getElementsByTagName('button')[0].click();})");
        $flashvars="&callback={$callback}&filename=new_recording";
		
		//make our insert string
        $recorder = '<div style="position:absolute; top:0;left:0;right:0;bottom:0; background-color:#fff;">
                <input type="hidden"  name="upload_filename" id="upload_filename" value="sausages.mp3"/>
                <textarea name="upload_filedata" id="upload_filedata" style="display:none;"></textarea>
               <!-- <textarea name="filename" id="filename" style="display:none;">sausages.mp3</textarea>
                 <textarea name="repo_upload_file" id="repo_upload_file" style="display:none;"></textarea> -->
                <div id="onlineaudiorecordersection" style="margin:20% auto; text-align:center;">
                    <object id="onlineaudiorecorder" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="215" height="138">
                        <param name="movie" value="'.$url.$flashvars.'" />
                        <param name="wmode" value="transparent" />
                        <!--[if !IE]>-->
                        <object type="application/x-shockwave-flash" data="'.$url.$flashvars.'" width="215" height="138">
                        <!--<![endif]-->
                        <div>
                                <p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
                        </div>
                        <!--[if !IE]>-->
                        </object>
                        <!--<![endif]-->
                    </object>
                </div>
            </div>';
			
			//return the recorder string
			return $recorder;
	
	}
	
	//return the html for the camera
	private function fetch_snapshottaker(){
			return "";
	}
	
	//return the html for the drawpad
	private function fetch_drawpad(){
		return "";
	}
	
	
	   /**
     * Thisplugin doesn't support global search
     */
    public function global_search() {
        return false;
    }

    public function get_listing($path='', $page = '') {
        return array();
    }


    /**
     * supported return types
     * @return int
     */
    public function supported_returntypes() {
        return FILE_INTERNAL;
    }
	
}//end of poodll plus repo class
