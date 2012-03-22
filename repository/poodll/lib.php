<?php // $Id$

/**
 * repository_poodll
 * Moodle user can record/play poodll audio/video items
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
 
//Get our poodll resource handling lib
require_once($CFG->dirroot . '/filter/poodll/poodllresourcelib.php');
 
class repository_poodll extends repository {
    /*
     * Begin of File picker API implementation
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        global $action, $itemid;
        parent::__construct ($repositoryid, $context, $options);
     
    }
    
    public static function get_instance_option_names() {
    	return array('recording_format');
    }
    
    public function instance_config_form($mform) {
        $recording_format_options = array(
        	get_string('audio', 'repository_poodll'),
        	get_string('video', 'repository_poodll'),
        );
        
        $mform->addElement('select', 'recording_format', get_string('recording_format', 'repository_poodll'), $recording_format_options);
        
        $mform->addRule('recording_format', get_string('required'), 'required', null, 'client');
		
    }
	
	


	//login overrride start
	//*****************************************************************
	//
     // Generate search form
     //
    public function print_login($ajax = true) {
		global $CFG,$PAGE,$USER;
		
		/*
	$rec = new stdClass();
        $rec->type = 'static';
        $rec->options = array();
        $rec->id = 'description';
        $rec->name = 'description';
		//$rec->value = format_string($this->fetch_recorder(),false);
        $rec->label = '';
*/
		

        $ret = array();
        $search = new stdClass();
        $search->type = 'hidden';
        $search->id   = 'filename';
        $search->name = 's';
		//$search->label = '<object data="' .$CFG->wwwroot . '/repository/poodll/recorder.php?repo_id=' . $this->id . '" style="height: 400px; width: 640px; border: 0; overflow: hidden;"  id="poodll-embed" ></object>';
		$search->label = "<iframe scrolling=\"no\" frameBorder=\"0\" src=\"{$CFG->wwwroot}/repository/poodll/recorder.php?repo_id={$this->id}\" height=\"350\" width=\"450\"></iframe>"; 
		//$search->label = "hi";



		$sort = new stdClass();
        $sort->type = 'hidden';
        $sort->options = array();
        $sort->id = 'youtube_sort';
        $sort->name = 'youtube_sort';
        $sort->label = '';

        $ret['login'] = array($search, $sort);
        $ret['login_btn_label'] = 'Next >>>';
        $ret['login_btn_action'] = 'search';
        return $ret;

    }
	

	public function check_login() {
        return !empty($this->keyword);
    }

		
	  
     // Method to get the repository content.
     //
     // @param string $path current path in the repository
     // @param string $page current page in the repository path
     // @return array structure of listing information
     //
    public function get_listing($path='', $page='') {
        global $CFG, $action;
			return  array();
		
   }
   
   ///
     // Return search results
     // @param string $search_text
     // @return array
     //
    public function search($filename) {
        $this->keyword = $filename;
        $ret  = array();
        $ret['nologin'] = true;
		$ret['nosearch'] = true;
        $ret['norefresh'] = true;
        //echo $filename;
		$ret['list'] = $this->fetch_file($filename);
		
        return $ret;
    }
	
 
     //login overrride end
	//*****************************************************************
  
  
  
  
 /*
	//search overrride start
	//*****************************************************************
		
	  //
     // Print a upload form
     // @return array

    public function print_login() {
        return $this->get_listing();
    }

	
    //
    // Return a upload form
    // @return array
     
    public function get_listing() {
        global $CFG;
        $ret = array();
        $ret['nologin']  = true;
        $ret['nosearch'] = false;
        $ret['norefresh'] = true;
        $ret['list'] = array();
        //$ret['dynload'] = false;
        //$ret['upload'] = array('label'=>get_string('attachment', 'repository'), 'id'=>'repo-form');
        return $ret;
    }

	
	///
    // Show the search screen, if required
    // @return null
   
    public function print_search() {
        $str = '';
        $str .= '<input type="hidden" name="savemedia" value="" />';
        $str .= $this->fetch_recorder();
        return $str;
    }
	//search overrride end
	//*****************************************************************

	*/
    
	
	    /**
     * Private method to get youtube search results
     * @param string $keyword
     * @param int $start
     * @param int $max max results
     * @param string $sort
     * @return array
     */
    private function fetch_file($filename) {
		global $CFG;
	
        $list = array();
		
		//if user did not record anything, or the recording copy failed can out sadly.
		if(!$filename){return $list;}
		
        $source="http://" . $CFG->filter_poodll_servername . 
						":443/poodll/download.jsp?poodllserverid=" . 
						$CFG->filter_poodll_serverid . "&filename=" . $filename . "&caller=" . urlencode($CFG->wwwroot);
						

            $list[] = array(
                'title'=>$filename,
                'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/bigicon.png",
                'thumbnail_width'=>440,
                'thumbnail_height'=>180,
                'size'=>'',
                'date'=>'',
                'source'=>$source
            );
       
        return $list;
    }
	

	  /**
     * Box.net supports file linking and copying
     * @return string
     */
    public function supported_returntypes() {
        return FILE_INTERNAL;
    }
	


    /**
     * Returns the suported returns values.
     * 
     * @return string supported return value
     */
    public function supported_return_value() {
        return 'ref_id';
    }

    /**
     * Returns the suported file types
     *
     * @return array of supported file types and extensions.
     */
    public function supported_filetypes() {
        return array('.flv');
    }
    
    /*
     * End of File picker API implementation
     */
    public function fetch_recorder() {
        global $USER,$CFG;
        $usercontextid = get_context_instance(CONTEXT_USER, $USER->id)->id;
		$draftitemid=0;
	//	$ret = '<form name="poodll_repository" action="' . $CFG->wwwroot . '/repository/poodll/recorder.php">';
	if($this->options['recording_format'] == 0){
		$ret = fetchSimpleAudioRecorder('swf','poodllrepository',$USER->id,'filename');
	}else{
		$ret = fetchSimpleVideoRecorder('swf','poodllrepository',$USER->id,'filename','','298', '340');
	}
		//$ret .= '<input type="text" id="filename" name="filename"/>';
		echo $ret;
		// onchange="parent.document.getElementById(\'filename\').value=\'7\';"
	}
}