<?php // $Id: assignment.class.php,v 1.46.2.6 2008/04/15 03:40:09 moodler Exp $

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir . '/portfoliolib.php');
require_once($CFG->dirroot . '/mod/assignment/lib.php');
require_once($CFG->libdir . '/filelib.php');

//Added Justin 2009/06/11 For printing to PDF

//Get our poodll resource handling lib
require_once($CFG->libdir . '/poodllresourcelib.php');
require_once($CFG->libdir . '/poodllfilelib.php');
//require_once('lib.php');


//some constants for the type of online poodll assignment
define('OM_REPLYTEXTONLY',0);
define('OM_REPLYVOICEONLY',1);
define('OM_REPLYVOICETHENTEXT',2);
define('OM_REPLYVIDEOONLY',3);
define('OM_REPLYVIDEOTHENTEXT',4);
define('OM_REPLYTALKBACK',5);
define('OM_FEEDBACKTEXT',0);
define('OM_FEEDBACKTEXTVOICE',1);
define('OM_FEEDBACKTEXTVIDEO',2);
define('HTML_FORMAT',1);
define('TCPPDF_OLD',0);

define('FILENAMECONTROL','saveflvvoice');

/**
 * Extend the base assignment class 
 *
 */
class assignment_poodllonline extends assignment_base {

	var $filearea = 'submission';

    function assignment_poodllonline($cmid='staticonly', $assignment=NULL, $cm=NULL, $course=NULL) {	
	
        parent::assignment_base($cmid, $assignment, $cm, $course);
        $this->type = 'poodllonline';
    }

	
    function view() {

        global $CFG, $USER, $DB , $OUTPUT, $PAGE;

        $edit  = optional_param('edit', 0, PARAM_BOOL);
        $saved = optional_param('saved', 0, PARAM_BOOL);
		$print  = optional_param('print', 0, PARAM_BOOL);
		
        $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        require_capability('mod/assignment:view', $context);

        $submission = $this->get_submission();
		
		//We added an extra field to the submissions table, for feedback using video or audio
		//and this was where we added it. But we will no longer do this in PoodLL 2. It was hacky.
		//can just drop in a video from a recording repo if want to do this.  But never say never so 
		//am leaving the code around. Justin 20120302
		if(false && $submission){
				$dbman = $DB->get_manager();
				$table = new xmldb_table('assignment_submissions');
				if (!$dbman->field_exists($table,'poodllfeedback')){
					
					$field = new xmldb_field('poodllfeedback', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null);
					$result = $dbman->add_field($table,$field);
					
				}
		}
			
		//Justin	
		//Are we printing this or not
		if ($print){
			if (TCPPDF_OLD){
				require_once($CFG->libdir . '/tcpdf/tcpdf.php');
			}else{
				require_once($CFG->libdir . '/newtcpdf/tcpdf.php');
			}

			
			 $pdf = new tcpdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true);
			// remove default header/footer
			//old version of tcppdf
			if (TCPPDF_OLD){
				$pdf->print_header = false;
				$pdf->print_footer = false;
			}else{
			//new version of tcppdf
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false); 
			}


			//set margins
			$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);


			//set auto page breaks
			$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM); 	
			$pdf->setFont('freeserif','',10);
			

			
			//make page
			$pdf->AddPage();
			
			//prepare html content
			 $options = new object();
             $options->smiley = false;
             $options->filter = false;
			 $strHtml = format_text($submission->data1, FORMAT_HTML, $options);
			 
			 
			//print the thing
			$pdf->writeHTML($strHtml,true,0,true,0); 
			//The I is for inline, meaning tell the browser to shopw not download it.
			$pdf->output('document.pdf', 'I');
			//$pdf->output();
			return;
		}
		

        //Guest can not submit nor edit an assignment (bug: 4604)
        if (!has_capability('mod/assignment:submit', $context)) {
            $editable = null;
        } else {
            $editable = $this->isopen() && (!$submission || $this->assignment->resubmit || !$submission->timemarked);
        }
		
		//modify Justin 20090305, we don't want to add this extra step for users.
		//If they can edit, and they haven't submitted anything, then lets just show the form.
		//If they have submitted something, lets give them an extra step if ytthey want to submit
		//to protect accidental overwrite of their submission.
       // $editmode = ($editable and $edit);
	    $editmode = ($editable and (!$submission || $edit));

        if ($editmode) {
            //guest can not edit or submit assignment
            if (!has_capability('mod/assignment:submit', $context)) {
                print_error('guestnosubmit', 'assignment');
            }
        }

        add_to_log($this->course->id, "assignment", "view", "view.php?id={$this->cm->id}", $this->assignment->id, $this->cm->id);

	/// prepare form and process submitted data
	//load it with some info it needs to determine the params for PoodLL recorder.
		//for voice then text, we need to know if we already have voice or not
		if(empty($submission)){
				$mediapath="";
			}else{
				$mediapath=$submission->data2;
		}
		
		  $data = new stdClass();
            $data->id         = $this->cm->id;
            $data->edit       = 1;
            if ($submission) {
                $data->sid        = $submission->id;
                $data->text       = $submission->data1;
                $data->textformat = FORMAT_HTML;
            } else {
                $data->sid        = NULL;
                $data->text       = '';
                $data->textformat = FORMAT_HTML;
            }
		
		
		$editoroptions = array('noclean'=>false, 'maxfiles'=>EDITOR_UNLIMITED_FILES, 'maxbytes'=>$this->course->maxbytes,'context'  => $this->context);
		$data = file_prepare_standard_editor($data, 'text', $editoroptions, $this->context, 'mod_assignment', $this->filearea, $data->sid);
		
        $mform = new mod_assignment_poodllonline_edit_form(null, array("cm"=>$this->cm,"assignment"=>$this->assignment,"mediapath"=>$mediapath,"data"=>$data, "editoroptions"=>$editoroptions));
/*
        $defaults = new object();
        $defaults->id = $this->cm->id;
        if (!empty($submission)) {
			//we always use html editor: Justin 20090225
            //if ($this->usehtmleditor) {
			if (true) {
                $options = new object();
                $options->smiley = false;
                $options->filter = false;

                $defaults->text   = format_text($submission->data1, FORMAT_HTML, $options);
                $defaults->format = FORMAT_HTML;
            } else {
                $defaults->text   = $submission->data1;
                $defaults->format = $submission->data2;
            }
        }
        $mform->set_data($defaults);
*/
        if ($mform->is_cancelled()) {
                redirect($PAGE->url);
            }

            if ($data = $mform->get_data()) {
                $submission = $this->get_submission($USER->id, true); //create the submission if needed & its id
				//this step is only required if we are using a text editor, it is to move drft files over Justin 20120208
				if($this->assignment->var3== OM_REPLYTEXTONLY){
					$data = file_postupdate_standard_editor($data, 'text', $editoroptions, $this->context, 'mod_assignment', $this->filearea, $submission->id);
				}
                $submission = $this->update_submission($data);

                //TODO fix log actions - needs db upgrade
                add_to_log($this->course->id, 'assignment', 'upload', 'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
                $this->email_teachers($submission);

                //redirect to get updated submission date and word count
                redirect(new moodle_url($PAGE->url, array('saved'=>1)));
            }
        

/// print header, etc. and display form if needed
       
		if ($editmode) {
            $this->view_header(get_string('editmysubmission', 'assignment'));
        } else {
            $this->view_header();
        }
		
		
        $this->view_intro();

        $this->view_dates();
		
	

        if ($saved) {
            notify(get_string('submissionsaved', 'assignment'), 'notifysuccess');
        }

        if (has_capability('mod/assignment:submit', $context)) {
			echo $OUTPUT->box_start('generalbox boxaligncenter', 'poodllonline');
           // print_simple_box_start('center', '70%', '', 0, 'generalbox', 'poodllonline');
            if ($editmode) {
					
				if ($submission) {				
				 
					 //Show our  students answer box
					 echo get_string('mysubmission', 'assignment_poodllonline');
					 echo $OUTPUT->box_start('generalbox boxaligncenter', 'mysubmission');
					 //print_simple_box_start('center', '50%', '', 0, 'generalbox', 'mysubmission');
					  
				echo $this->fetchResponses($this->context->id,$submission->id,$this->assignment->var3,$submission->data1,$submission->data2);
				/*
				//check if we need media output
					switch($this->assignment->var3){
						
						case OM_REPLYVOICEONLY:
							$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';							
							echo format_text('{POODLL:type=audio,path='.	$mediapath .',protocol=http}', FORMAT_HTML);
							break;						

						case OM_REPLYVIDEOONLY:
							$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';								
							echo format_text('{POODLL:type=video,path='.	$mediapath .',protocol=http}', FORMAT_HTML);						
							break;

						
						case OM_REPLYVOICETHENTEXT:						
							$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';							
							echo format_text('{POODLL:type=audio,path='.	$mediapath .',protocol=http}', FORMAT_HTML);
							break;

						case OM_REPLYVIDEOTHENTEXT:						
							$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';								
							echo format_text('{POODLL:type=video,path='.	$mediapath .',protocol=http}', FORMAT_HTML);
							break;
			
					}
					
					//check if we need text output	
					switch($this->assignment->var3){
						case OM_REPLYVOICETHENTEXT:
						case OM_REPLYVIDEOTHENTEXT:	
							if(empty($submission->data1)){
								break;
							}else{
								echo "<br />";
							}
							
						case OM_REPLYTEXTONLY:
						default:	
							echo format_text($submission->data1, FORMAT_HTML);
					}
					*/

					//Close our students answer box
					//print_simple_box_end();
					echo $OUTPUT->box_end();
				}

			
					$mform->display();				
            } else {
                if ($submission) {
					
					//Show our  students answer box
					echo get_string('mysubmission', 'assignment_poodllonline');
					echo $OUTPUT->box_start('generalbox boxaligncenter', 'mysubmission');
					//print_simple_box_start('center', '50%', '', 0, 'generalbox', 'mysubmission');
					
					echo $this->fetchResponses($this->context->id,$submission->id,$this->assignment->var3,$submission->data1,$submission->data2);
					
					/*
					switch($this->assignment->var3){
						
						case OM_REPLYVOICEONLY:
						
							$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';							
							//echo $mediapath . "<br />";
							echo format_text('{POODLL:type=audio,path='.	$mediapath .',protocol=http}', FORMAT_HTML);							
							break;

						case OM_REPLYVIDEOONLY:
							
							
							$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';								
							//echo $mediapath . "<br />";	
							
						//for debugging get a list of files	
						//
						//$fs = get_file_storage();
						//if (($submission) && $files = $fs->get_area_files($this->context->id, 'mod_assignment', 'submission', $submission->id, "timemodified", false)) {
						//	foreach ($files as $file) {
						//		$filename = $file->get_filename();
						//		echo "<br />filename:" . $filename;
						//	echo "<br />path:" . $file->get_contextid() . "/" . $file->get_component()  . "/" . $file->get_filearea()   . "/" . $file->get_itemid()  . "/" . $file->get_filename();
						//	}
						//	echo "<br />finished" ;
						//}
						 ///
						//over debugging

					
							echo format_text('{POODLL:type=video,path='.	$mediapath .',protocol=http}', FORMAT_HTML);							
							break;

						
						case OM_REPLYVOICETHENTEXT:						
							$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';							
							//echo $mediapath . "<br />";
							echo format_text('{POODLL:type=audio,path='.	$mediapath .',protocol=http}', FORMAT_HTML);
							break;

						case OM_REPLYVIDEOTHENTEXT:						
							$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';								
							echo format_text('{POODLL:type=video,path='.	$mediapath .',protocol=http}', FORMAT_HTML);
							break;
						
						

					}
					
					
					//check if we need text output	
					switch($this->assignment->var3){
						case OM_REPLYVOICETHENTEXT:
						case OM_REPLYVIDEOTHENTEXT:	
							if(empty($submission->data1)){
								break;
							}else{
								echo "<br />";
							}
							
						case OM_REPLYTEXTONLY:
						default:	
							echo format_text($submission->data1, FORMAT_HTML);
					}
					
					*/
					
					//Close out students answer box
					//print_simple_box_end();
                     echo $OUTPUT->box_end();
                
				
				
				
				} else if (!has_capability('mod/assignment:submit', $context)) { //fix for #4604
                    echo '<div style="text-align:center">'. get_string('guestnosubmit', 'assignment').'</div>';
                } else if ($this->isopen()){    //fix for #4206
                    echo '<div style="text-align:center">'.get_string('emptysubmission', 'assignment').'</div>';
                }
            }
           // print_simple_box_end();
		    echo $OUTPUT->box_end();
            if (!$editmode && $editable) {
                echo "<div style='text-align:center'>";
               
				
			//this method is deprecated , we now use $OUTPUT  Justin 20110602
			 //  print_single_button('view.php', array('id'=>$this->cm->id,'edit'=>'1'),
              //          get_string('editmysubmission', 'assignment'));	
			echo $OUTPUT->single_button('view.php?id=' . $this->cm->id . '&edit=1',
                        get_string('editmysubmission', 'assignment'));
						
						
                echo "</div>";
            }
		
			//show a print buttonif it is text only and not edit mode	
			if ($this->assignment->var3 == OM_REPLYTEXTONLY && !$editmode){
					echo "<br /><div style='text-align:center'>";					
					echo "<a href='view.php?id=" . $this->cm->id . "&print=1' target='_new'>" . get_string('printthissubmission', 'assignment_poodllonline') . 								"</a>";
									
					//The target tag is ignored by print_single_button so not using it
					//print_single_button('view.php', array('id'=>$this->cm->id,'print'=>'1'),get_string('printthissubmission', 'assignment_poodllonline'),'get','_new');		
					echo "</div>";
		
			}//end of if printable		

        }//end of if can submit

        $this->view_feedback();

        $this->view_footer();
    }

    /*
     * Display the assignment dates
     */
    function view_dates() {
        global $USER, $CFG, $OUTPUT;

        if (!$this->assignment->timeavailable && !$this->assignment->timedue) {
            return;
        }
		
		echo $OUTPUT->box_start('generalbox boxaligncenter', 'dates');
       // print_simple_box_start('center', '', '', 0, 'generalbox', 'dates');
		 
        echo '<table>';
        if ($this->assignment->timeavailable) {
            echo '<tr><td class="c0">'.get_string('availabledate','assignment').':</td>';
            echo '    <td class="c1">'.userdate($this->assignment->timeavailable).'</td></tr>';
        }
        if ($this->assignment->timedue) {
            echo '<tr><td class="c0">'.get_string('duedate','assignment').':</td>';
            echo '    <td class="c1">'.userdate($this->assignment->timedue).'</td></tr>';
        }
        $submission = $this->get_submission($USER->id);
        if ($submission) {
            echo '<tr><td class="c0">'.get_string('lastedited').':</td>';
            echo '    <td class="c1">'.userdate($submission->timemodified);
        /// Decide what to count
            if ($CFG->assignment_itemstocount == ASSIGNMENT_COUNT_WORDS) {
                echo ' ('.get_string('numwords', '', count_words(format_text($submission->data1, FORMAT_HTML))).')</td></tr>';
            } else if ($CFG->assignment_itemstocount == ASSIGNMENT_COUNT_LETTERS) {
                echo ' ('.get_string('numletters', '', count_letters(format_text($submission->data1, FORMAT_HTML))).')</td></tr>';
            }
        }
        echo '</table>';
        //print_simple_box_end();
		echo $OUTPUT->box_end();
    }

    function update_submission($data) {
        global $CFG, $USER, $DB,$COURSE;

        $submission = $this->get_submission($USER->id, true);
		
		//a hack into moodle form system to have moodle get the filename of the file we recorded. 
		//When we add the recorder via the poodll filter, it adds a hidden form field of the name FILENAMECONTROL
		//the recorder updates that field with the filename of the audio/video it recorded. We pick up that filename here.
		
		
		$filename = optional_param(FILENAMECONTROL, '', PARAM_RAW);
		$draftitemid = optional_param('draftitemid', '', PARAM_RAW);
		$usercontextid = optional_param('usercontextid', '', PARAM_RAW);
		 $fs = get_file_storage();
		 $browser = get_file_browser();
         $fs->delete_area_files($this->context->id, 'mod_assignment', 'submission', $submission->id);

		//$filehash = sha1('/' . $usercontextid . '/user/draft/' . $draftitemid . '/'. $filename);
		//$ret = instance_duplicatefile('mod_assignment', $COURSE->id, $submission->id, 'submission', $filename, $filehash, '1234567'){
		
		
		
			//fetch the file info object for our original file
		$original_context = get_context_instance_by_id($usercontextid);
		$draft_fileinfo = $browser->get_file_info($original_context, 'user','draft', $draftitemid, '/', $filename);
	
		//perform the copy	
		if($draft_fileinfo){
			$ret = $draft_fileinfo->copy_to_storage($this->context->id, 'mod_assignment', 'submission', $submission->id, '/', $filename);
			//$return['success'] =false;
			
		}//end of if $original_fileinfo

		
		
	//	$filesaved = $mform->save_stored_file(FILENAMECONTROL, $this->context->id, 'mod_assignment', 'submission',
     //              $submission->id, '/', $filename);


		//instance_remotedownload params = $contextid,$filename,$component, $filearea,$itemid, $requestid(this can be random)
		//$sid=$submission->id;
        //$filesaved = instance_remotedownload($this->context->id,$filename,"mod_assignment", "submission",$sid, "1234523");
		
		/* ================================= start of old filecopy logic
		$red5_fileurl="http://" . $CFG->filter_poodll_servername . 
						":443/poodll/download.jsp?poodllserverid=" . 
						$CFG->filter_poodll_serverid . "&filename=" . $filename;


		if (!empty($filename)){
		//copy file from red5 over to user submissions area.
		//====================================================
		//moodle 2 change
		//$this->context
		
		//from single_upload_file assignment, just so we know how to do this
		//  $file = $mform->save_stored_file('assignment_file', $this->context->id, 'mod_assignment', 'submission',
         //               $submission->id, '/', $newfilename);
		
		
		//The filepath causes problems, if we use  single / or path surrounded by slashes saves
		//but gives an error like this Can not create file "29/mod_assignment/submission/2//recording//insupermarket.flv"
		//If we use empty string or plain word, we get invalid filepath and nothing is saved.
		$fs = get_file_storage();
		$sid=$submission->id;
		$file_record = array(
		'contextid'=>$this->context->id, 
		'component'=>'mod_assignment', 
		'filearea'=>'submission',
        'itemid'=>$sid, 
		'filepath'=>'/', 
		'filename'=>$filename,         
		'timecreated'=>time(), 
		'timemodified'=>time()
		);
	
		 //calculate a hash for later convenience
		 $pathhash = $fs->get_pathname_hash($file_record['contextid'], $file_record['component'], 
				$file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename']);
				
		//if the said file already exists, lets delete it before we try to overwrite it		
		 if($fs->file_exists_by_hash($pathhash)){
			
			//get a handle on the existing file
			$dafile = $fs->get_file_by_hash($pathhash);
			
			//Initially tried to delete the file in this way, but it didn't work. It only works if 
			//if I deleted all area files by not adding the dafile id parameter. If dafile->delete causes problems
			//we can look at this method again. Justin 20110602
			//	$success = $fs->delete_area_files($file_record['contextid'], $file_record['component'], 
			//			$file_record['filearea'],$dafile->get_id());			
			
			//To delete all files use this
			//$success = $fs->delete_area_files($file_record['contextid'], $file_record['component'], $file_record['filearea'],$dafile->get_id());
			
			//To delete just the file we are submitting do this.
			$dafile->delete();
			

		}
		
		 //now we copy over the file from red5 to here.
		 //We can do it by url or by path. url is good for remote servers like our hosted red5
		//$fs->create_file_from_pathname($file_record, $red5_filepath);
		$fs->create_file_from_url($file_record, $red5_fileurl);

		//=====================================================
		
	}//end of if (!empty($filename)){
	//===================================================== end of old file copy logic
	*/
	   $update = new stdClass();
        $update->id           = $submission->id;
		$update->data2        = "";
        if (!empty($data->text)){
			$update->data1  = $data->text;
		}else{
			$update->data1 = "";
		}

		//update media field with data that the PoodLL filter will pick up
		if (!empty($filename)){
			//$update->data2         = $data->saveflvvoice;
			$update->data2         = $filename;
		}
	
		$update->timemodified = time();
        $DB->update_record('assignment_submissions', $update);
        $submission = $this->get_submission($USER->id);
        $this->update_grade($submission);
        return true;
    }

	  /**
     * 
     * Check if it is ok to return  a submitted file
     * then return it, or an error. But not both!
     *  This overrides an assignment default send_file and 
     *  and is looked for by pluginfile.php Justin 20110604 
      */
    function send_file($filearea, $args) {
        global $CFG, $DB, $USER;
		ob_start();
        require_once($CFG->libdir.'/filelib.php');

        require_login($this->course, false, $this->cm);

        if ($filearea !== 'submission' && $filearea !== 'response') {
            return false;
        }

        $submissionid = (int)array_shift($args);

        if (!$submission = $DB->get_record('assignment_submissions', array('assignment'=>$this->assignment->id, 'id'=>$submissionid))) {
            return false;
        }

        if ($USER->id != $submission->userid and !has_capability('mod/assignment:grade', $this->context)) {
            return false;
        }

        $relativepath = implode('/', $args);
        $fullpath = '/'.$this->context->id.'/mod_assignment/'.$filearea.'/'.$submissionid.'/'.$relativepath;

        $fs = get_file_storage();

        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }
		ob_clean();
        send_stored_file($file, 0, 0, true); // download MUST be forced - security!
    }

	
	 function can_manage_responsefiles() {
        if (has_capability('mod/assignment:grade', $this->context)) {
            return true;
        } else {
            return false;
        }
    }
	
	
	 function print_responsefiles($userid, $return=false) {
        global $CFG, $USER, $OUTPUT, $PAGE;

        $mode    = optional_param('mode', '', PARAM_ALPHA);
        $offset  = optional_param('offset', 0, PARAM_INT);

        $output = $OUTPUT->box_start('responsefiles');

        $candelete = $this->can_manage_responsefiles();
        $strdelete   = get_string('delete');

        $fs = get_file_storage();
        $browser = get_file_browser();

        if ($submission = $this->get_submission($userid)) {
            $renderer = $PAGE->get_renderer('mod_assignment');
            $output .= $renderer->assignment_files($this->context, $submission->id, 'response');
            $output .= $OUTPUT->box_end();
        }

        if ($return) {
            return $output;
        }
        echo $output;
    }
	
	
    function print_student_answer($userid, $return=false){
        global $CFG, $OUTPUT, $PAGE;
		if (empty($PAGE)) {
			$jsadded="jas not added";
		}else{
			//use this to allow javascript
			$jsadded="jas dded";
		//	$PAGE->requires->js(new moodle_url($CFG->httpswwwroot . '/mod/assignment/type/poodllonline/swfobject.js'));
		//	$PAGE->requires->js(new moodle_url($CFG->httpswwwroot .'/mod/assignment/type/poodllonline/javascript.js'));
		}
		
        if (!$submission = $this->get_submission($userid)) {
            return '';
        }
		
		
		if($this->assignment->var3==OM_REPLYTEXTONLY){
			$showtext =shorten_text(trim(strip_tags(format_text($submission->data1,FORMAT_HTML))), 15);
				   
			$link = new moodle_url("/mod/assignment/type/poodllonline/file.php?id={$this->cm->id}&userid={$submission->userid}");
			$action = new popup_action('click', $link, 'file'.$userid, array('height' => 450, 'width' => 580));
			$showtext = $OUTPUT->action_link($link, $showtext, $action, array('title'=>get_string('submission', 'assignment')));
            $showtext=  '<img src="'.$OUTPUT->pix_url('f/html') . '" class="icon" alt="html" />'. $showtext ;
		
		}else{
		
			$showtext = $this->fetchResponses($this->context->id,$submission->id,$this->assignment->var3,$submission->data1,$submission->data2, true, false);
		
		}

	/*   		  
		//Output user input Audio and Text, depending on assignment type.
		switch($this->assignment->var3){
			
			case OM_REPLYVOICEONLY:
				if (!empty($submission->data2)){ 
							$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';								
							$showtext = format_text('{POODLL:type=audio,path='.	$mediapath .',protocol=http,embed=true}', FORMAT_HTML);
				}else{
					$showtext= "No Audio Found.";
				}
				break;

			case OM_REPLYVIDEOONLY:
				if (!empty($submission->data2)){ 
					$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';								
					$showtext = format_text('{POODLL:type=video,path='.	$mediapath .',protocol=http}', FORMAT_HTML);
				}else{
					$showtext= "No Video Found.";
				}
				break;
			
			case OM_REPLYVOICETHENTEXT:	
				if (!empty($submission->data2)){ 					
					$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';								
					$showtext = format_text('{POODLL:type=audio,path='.	$mediapath .',protocol=http}', FORMAT_HTML);

				}else{
					$showtext = "No Audio Found.";					
				}
				break;
			case OM_REPLYVIDEOTHENTEXT:	
				if (!empty($submission->data2)){ 					
						$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2;								
						$showtext = format_text('{POODLL:type=video,path='.	$mediapath .',protocol=http}', FORMAT_HTML);
				}else{
					$showtext = "No Video Found.";
				}
				break;
			case OM_REPLYTEXTONLY:
			default:
				   $showtext =shorten_text(trim(strip_tags(format_text($submission->data1,FORMAT_HTML))), 15);
				   
				   $link = new moodle_url("/mod/assignment/type/poodllonline/file.php?id={$this->cm->id}&userid={$submission->userid}");
					$action = new popup_action('click', $link, 'file'.$userid, array('height' => 450, 'width' => 580));
				$showtext = $OUTPUT->action_link($link, $showtext, $action, array('title'=>get_string('submission', 'assignment')));

                 $showtext=  '<img src="'.$OUTPUT->pix_url('f/html') . '" class="icon" alt="html" />'. $popup ;
                 
		}				  
				  
	*/


		// we use this in place of popup if you want to show little play links, have to do some javascript fixing though
		$output = '<div class="files">';
		$output .= $showtext;
		$output .= '</div>';
        return $output;		
	
    }
	
	
	

    function print_user_files($userid, $return=false) {
        global $CFG, $OUTPUT;
		$returnString="";
        if (!$submission = $this->get_submission($userid)) {
            return $returnString;
        }

		$returnstring = $this->fetchResponses($this->context->id,$submission->id,$this->assignment->var3,$submission->data1,$submission->data2, true, true);
     /*  
     
		//Output user input Audio and Text, depending on assignment type.
		switch($this->assignment->var3){
			
			case OM_REPLYVOICEONLY:
				if (!empty($submission->data2)){ 
				
				$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';								
			//	 echo $OUTPUT->box_start('generalbox boxaligncenter', 'online');
				$returnString = format_text('{POODLL:type=audio,path='. $mediapath .',protocol=http}', FORMAT_HTML);
			//	echo $OUTPUT->box_end();
					//print_simple_box(format_text('{FMS:VOICE='.	$submission->data2.'}', FORMAT_HTML), 'center', '100%');
					//print_simple_box(format_text('{POODLL:type=audio,path='.	$submission->data2.',protocol=rtmp}', FORMAT_HTML), 'center', '100%');
				}else{
					$returnString =  "No Audio Found.";
				}
				break;
				

			case OM_REPLYVIDEOONLY:
				if (!empty($submission->data2)){ 
					$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';								
					$returnString =  format_text('{POODLL:type=video,path='.	$mediapath .',protocol=http}', FORMAT_HTML);
				}else{
					$returnString =   "No Video Found.";
				}
				break;
			
			case OM_REPLYVOICETHENTEXT:	
				if (!empty($submission->data2)){ 
					$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';								
					$returnString =  format_text('{POODLL:type=audio,path='.	$mediapath .',protocol=http}', FORMAT_HTML);

				/// Decide what to count
					if ($CFG->assignment_itemstocount == ASSIGNMENT_COUNT_WORDS) {
						$returnString .= ' ('.get_string('numwords', '', count_words(format_text($submission->data1, FORMAT_HTML))).')';
					} else if ($CFG->assignment_itemstocount == ASSIGNMENT_COUNT_LETTERS) {
						$returnString  .=   ' ('.get_string('numletters', '', count_letters(format_text($submission->data1, FORMAT_HTML))).')';
					}
					
					//print text
					$returnString .= format_text($submission->data1, FORMAT_HTML);

					
				}else{
					$returnString = "No Audio Found.";
				}
				break;
			case OM_REPLYVIDEOTHENTEXT:	
				if (!empty($submission->data2)){ 
					$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$this->context->id.'/mod_assignment/submission/'.$submission->id.'/'. $submission->data2 . '?forcedownload=1';								
					$returnString = format_text('{POODLL:type=video,path='.	$mediapath .',protocol=http}', FORMAT_HTML);

				/// Decide what to count
					if ($CFG->assignment_itemstocount == ASSIGNMENT_COUNT_WORDS) {
						$returnString .= ' ('.get_string('numwords', '', count_words(format_text($submission->data1, FORMAT_HTML))).')';
					} else if ($CFG->assignment_itemstocount == ASSIGNMENT_COUNT_LETTERS) {
						$returnString .= ' ('.get_string('numletters', '', count_letters(format_text($submission->data1, FORMAT_HTML))).')';
					}

					//print text
					$returnString .= format_text($submission->data1, FORMAT_HTML);

					
				}else{
					$returnString .= "No Video Found.";
				}
				break;
			case OM_REPLYTEXTONLY:
			default:
				/// Decide what to count
					if ($CFG->assignment_itemstocount == ASSIGNMENT_COUNT_WORDS) {
						$returnString .=  ' ('.get_string('numwords', '', count_words(format_text($submission->data1, FORMAT_HTML))).')';
					} else if ($CFG->assignment_itemstocount == ASSIGNMENT_COUNT_LETTERS) {
						$returnString .=  ' ('.get_string('numletters', '', count_letters(format_text($submission->data1, FORMAT_HTML))).')';
					}
		
					//print text
					$returnString .=  format_text($submission->data1, FORMAT_HTML);
					
				
		}
		//end of text and audio output switch
		*/
		
		return $returnString;
		
    }
	
	
	function fetchResponses($contextid, $submissionid, $submissiontype, $submissiontext, $submissionfile,$checkfordata=false, $fromuserfiles=false){
		global $CFG;
		
		$responsestring = "";
		
		//if this is a playback area, for teacher, show a string if no file
		if ($checkfordata  && empty($submissionfile) && $submissiontype != OM_REPLYTEXTONLY){ 
					$responsestring .= "Nothing to play";
		}else{	
			//check if we need media output
			switch($submissiontype){
							
				case OM_REPLYVOICEONLY:
						$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'. $contextid .'/mod_assignment/submission/'.$submissionid.'/'. $submissionfile . '?forcedownload=1';							
						$responsestring .= format_text('{POODLL:type=audio,path='.	$mediapath .',protocol=http}', FORMAT_HTML);
						break;						
					
				case OM_REPLYVIDEOONLY:
					$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$contextid.'/mod_assignment/submission/'.$submissionid.'/'. $submissionfile . '?forcedownload=1';								
					$responsestring .= format_text('{POODLL:type=video,path='.	$mediapath .',protocol=http}', FORMAT_HTML);						
					break;

				
				case OM_REPLYVOICETHENTEXT:						
					$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$contextid.'/mod_assignment/submission/'. $submissionid.'/'. $submissionfile . '?forcedownload=1';							
					$responsestring .= format_text('{POODLL:type=audio,path='.	$mediapath .',protocol=http}', FORMAT_HTML);
					break;

				case OM_REPLYVIDEOTHENTEXT:						
					$mediapath = $CFG->wwwroot.'/pluginfile.php' . '/'.$contextid.'/mod_assignment/submission/'. $submissionid.'/'. $submissionfile . '?forcedownload=1';								
					$responsestring .= format_text('{POODLL:type=video,path='.	$mediapath .',protocol=http}', FORMAT_HTML);
					break;
				
			}//end of switch
		}//end of if (checkfordata ...) 
		
					
		//check if we need text output	
		switch($submissiontype){
			case OM_REPLYVOICETHENTEXT:
			case OM_REPLYVIDEOTHENTEXT:	
				//add a clear line if we have text after audio or video player
				if(empty($submissiontext)){
					break;
				}else{
					$responsestring .= "<br />";
				}
				
			case OM_REPLYTEXTONLY:
			default:	
				//if we are coming from print user files we also print the word count.
				if($fromuserfiles){
						/// Decide what to count
						if ($CFG->assignment_itemstocount == ASSIGNMENT_COUNT_WORDS) {
							$responsestring .= ' ('.get_string('numwords', '', count_words(format_text($submissiontext, FORMAT_HTML))).')';
						} else if ($CFG->assignment_itemstocount == ASSIGNMENT_COUNT_LETTERS) {
							$responsestring .= ' ('.get_string('numletters', '', count_letters(format_text($submissiontext, FORMAT_HTML))).')';
						}
				}
			
					//finally we print the text response
					$responsestring .= format_text($submissiontext, FORMAT_HTML);

			
				
		}//end of switch

		
		return $responsestring;
		
	}//end of fetchResponses
	

	
	/*
	*	Here we print out to pdf
	*	
	*/
	function printToPdf($htmlContent){
	
		$pdf= new pdf;		
		$pdf->print_header = false;
		$pdf->print_footer = false;
		$pdf->AddPage();
		$pdf->writeHTML($htmlcontent, true, 0, true, 0); 
		$pdf->output('document.pdf', 'I');
	}

    function preprocess_submission(&$submission) {

        if ($this->assignment->var1 && empty($submission->submissioncomment)) {  // comment inline

			//if ($this->usehtmleditor) {
			//we always use html editor
			if(true){
                // Convert to html, clean & copy student data to teacher
                $submission->submissioncomment = format_text($submission->data1, FORMAT_HTML);
                $submission->format = FORMAT_HTML;
            } else {
                // Copy student data to teacher
                $submission->submissioncomment = $submission->data1;
                $submission->format = $submission->data2;
            }
        }
    }

	
	
    function setup_elements(&$mform) {
        global $CFG, $COURSE;

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));		
        $mform->addElement('select', 'resubmit', get_string("allowresubmit", "assignment"), $ynoptions);
	//Commented Justin 20110602 moving to Moodle 2.0 - This function deprecated	, not sure of the replacement
     //   $mform->setHelpButton('resubmit', array('resubmit', get_string('allowresubmit', 'assignment'), 'assignment'));
        $mform->setDefault('resubmit', 0);

        $mform->addElement('select', 'emailteachers', get_string("emailteachers", "assignment"), $ynoptions);
		//Commented Justin 20110602 moving to Moodle 2.0 - This function deprecated	, not sure of the replacement	
     //   $mform->setHelpButton('emailteachers', array('emailteachers', get_string('emailteachers', 'assignment'), 'assignment'));
        $mform->setDefault('emailteachers', 0);

        $mform->addElement('select', 'var1', get_string("commentinline", "assignment"), $ynoptions);
		//Commented Justin 20110602 moving to Moodle 2.0 - This function deprecated	, not sure of the replacement	
    //    $mform->setHelpButton('var1', array('commentinline', get_string('commentinline', 'assignment'), 'assignment'));
        $mform->setDefault('var1', 0);
		
		
		$mform->addElement('header', 'onlinemediasettings', get_string('onlinemediasettings', 'assignment_poodllonline'));


		//reply method for student
		$qoptions[OM_REPLYTEXTONLY] = get_string('replytextonly', 'assignment_poodllonline');
		$qoptions[OM_REPLYVOICEONLY] = get_string('replyvoiceonly', 'assignment_poodllonline');
		$qoptions[OM_REPLYVIDEOONLY] = get_string('replyvideoonly', 'assignment_poodllonline');
		//We may re-enable these in the future. But for now in PoodLL 2.0 they are on hold Justin 20120208
		//$qoptions[OM_REPLYVOICETHENTEXT] = get_string('replyvoicethentext', 'assignment_poodllonline');
		//$qoptions[OM_REPLYVIDEOTHENTEXT] = get_string('replyvideothentext', 'assignment_poodllonline');           
		//$qoptions[OM_REPLYTALKBACK] = get_string('replytalkback', 'assignment_poodllonline');
        	$mform->addElement('select', 'var3', get_string('replytype', 'assignment_poodllonline'), $qoptions);
		
		//feedback method for teacher
		$qoptions=array();
		$qoptions[OM_FEEDBACKTEXT] = get_string('feedbacktext', 'assignment_poodllonline');
		//We may re-enable these in the future. But for PoodLL 2.0 they are on hold Justin 20120208
		//$qoptions[OM_FEEDBACKTEXTVOICE] = get_string('feedbacktextvoice', 'assignment_poodllonline');
		//$qoptions[OM_FEEDBACKTEXTVIDEO] = get_string('feedbacktextvideo', 'assignment_poodllonline');        
        $mform->addElement('select', 'var4', get_string('feedbacktype', 'assignment_poodllonline'), $qoptions);

    }
	
	//allow to add this submission to the portfolio
	function portfolio_exportable() {
        return true;
    }
	
	//Download all the submissions as one "set"
	function download_submissions() {
        global $CFG,$DB;
        require_once($CFG->libdir.'/filelib.php');

        $submissions = $this->get_submissions('','');
        if (empty($submissions)) {
            print_error('errornosubmissions', 'assignment');
        }
        $filesforzipping = array();
        $fs = get_file_storage();

        $groupmode = groups_get_activity_groupmode($this->cm);
        $groupid = 0;   // All users
        $groupname = '';
        if ($groupmode) {
            $groupid = groups_get_activity_group($this->cm, true);
            $groupname = groups_get_group_name($groupid).'-';
        }
        $filename = str_replace(' ', '_', clean_filename($this->course->shortname.'-'.$this->assignment->name.'-'.$groupname.$this->assignment->id.".zip")); //name of new zip file.
        foreach ($submissions as $submission) {
            $a_userid = $submission->userid; //get userid
            if ((groups_is_member($groupid,$a_userid)or !$groupmode or !$groupid)) {
                $a_assignid = $submission->assignment; //get name of this assignment for use in the file names.
                $a_user = $DB->get_record("user", array("id"=>$a_userid),'id,username,firstname,lastname'); //get user firstname/lastname

                $files = $fs->get_area_files($this->context->id, 'mod_assignment', 'submission', $submission->id, "timemodified", false);
                foreach ($files as $file) {
                    //get files new name.
                    $fileext = strstr($file->get_filename(), '.');
                    $fileoriginal = str_replace($fileext, '', $file->get_filename());
                    $fileforzipname =  clean_filename(fullname($a_user) . "_" . $fileoriginal."_".$a_userid.$fileext);
                    //save file name to array for zipping.
                    $filesforzipping[$fileforzipname] = $file;
                }
            }
        } // End of foreach
        if ($zipfile = assignment_pack_files($filesforzipping)) {
            send_temp_file($zipfile, $filename); //send file and delete after sending.
        }
    }
	

}

class mod_assignment_poodllonline_edit_form extends moodleform {
    function definition() {
		global $USER;
	
        $mform =& $this->_form;
		
				//Do we need audio or text? or both?
				//the customdata is info we passed in up around line 175 in the view method.
				switch($this->_customdata['assignment']->var3){
					
					case OM_REPLYVOICEONLY:
						//$mediadata= fetchSimpleAudioRecorder('onlinemedia' . $this->_customdata['cm']->id , $USER->id);
						$mediadata= fetchSimpleAudioRecorder('assignment/' . $this->_customdata['assignment']->id , $USER->id);
						
						//Add the PoodllAudio recorder. Theparams are the def filename and the DOM id of the filename html field to update
						//$mform->addElement('static', 'description', get_string('voicerecorder', 'assignment_poodllonline'),$mediadata);
						$mform->addElement('static', 'description', '',$mediadata);
						//chosho recorder needs to know the id of the checkobox to set it.
						//moodle uses unpredictable ids, so we make our own checkbox when we fetch chosho recorder
						//$mform->addElement('checkbox', FILENAMECONTROL, get_string('saverecording', 'assignment_poodllonline'));
						//$mform->addRule(FILENAMECONTROL, get_string('required'), 'required', null, 'client');
						break;

					case OM_REPLYVIDEOONLY:
						
						//$mediadata= fetchSimpleVideoRecorder('assignment/' . $this->_customdata['assignment']->id , $USER->id);	
						
						//if filenamecontrol is saveflvvoice the filter will add the form control
						//the problem is that we set the fields value by ID from the widget but the mform only deals with name attribute
						//$mform->addElement('hidden', FILENAMECONTROL, '');
						$draftitemid = file_get_submitted_draft_itemid(FILENAMECONTROL);
    					//file_prepare_draft_area($draftitemid, $contextid, $component, $filearea, $itemid, $options);
    					//draftitemid should get updated(its a pointer) contextid component and filearea maybe should be submission?
    					//whats context id? if we know submissionid we could pass it in
						$contextid=$this->_customdata['editoroptions']['context']->id;
						$submissionid=$this->_customdata['data']->sid;
    					file_prepare_draft_area($draftitemid, $contextid, 'mod_assignment', 'submission', $submissionid, null,null);
						
						 // $draftid_editor = file_get_submitted_draft_itemid($field.'_filemanager');
						//	file_prepare_draft_area($draftid_editor, $contextid, $component, $filearea, $itemid, $options);
						//$data->{$field.'_filemanager'} = $draftid_editor;
						
						
						$usercontextid=get_context_instance(CONTEXT_USER, $USER->id)->id;
						$mediadata= fetchVideoRecorderForSubmission('swf','poodllonline',FILENAMECONTROL, $usercontextid ,'user','draft',$draftitemid);
						$mform->addElement('static', 'description', '',$mediadata);			
						$mform->addElement('hidden', 'draftitemid', $draftitemid);
						$mform->addElement('hidden', 'usercontextid', $usercontextid);						
						break;
					
					case OM_REPLYVOICETHENTEXT:
						//if we have no audio, we force user to make audio before text
						if(empty($this->_customdata['mediapath'])){			
							//Add the PoodllAudio recorder. Theparams are the def filename and the DOM id of the filename html field to update
							//$mediadata= fetchSimpleAudioRecorder('onlinemedia' . $this->_customdata['cm']->id , $USER->id);
							$mediadata= fetchSimpleAudioRecorder('assignment/' . $this->_customdata['assignment']->id , $USER->id);
							//moodle uses unpredictable ids, so we make our own checkbox when we fetch chosho recorder
							//$mform->addElement('checkbox', FILENAMECONTROL, get_string('saverecording', 'assignment_poodllonline'));
							//$mform->addRule(FILENAMECONTROL, get_string('required'), 'required', null, 'client');
							$mform->addElement('static', 'description', '',$mediadata);
							//$mform->addElement('static', 'description', get_string('voicerecorder', 'assignment_poodllonline'),$mediadata);
							//we don't give option to write text, so break here
						}else{
						//It should be already displayed
						//at the top of the submission area
						//	$mediadata= format_text('{FMS:VOICE='.	$this->_customdata['mediapath'] .'}', FORMAT_HTML);							
						//	$mform->addElement('static', 'description', get_string('voicerecorder', 'assignment_poodllonline'),$mediadata);
						}
						break;

					case OM_REPLYVIDEOTHENTEXT:
						//if we have no video, we force user to make video before text
						if(empty($this->_customdata['mediapath'])){			
							//Add the Video recorder. Theparams are the def filename and the DOM id of the filename html field to update
							//$mediadata= fetchSimpleVideoRecorder('onlinemedia' . $this->_customdata['cm']->id , $USER->id);
							$mediadata= fetchSimpleVideoRecorder('assignment/' . $this->_customdata['assignment']->id , $USER->id);
							//moodle uses unpredictable ids, so we make our own checkbox when we fetch video recorder
							//$mform->addElement('checkbox', FILENAMECONTROL, get_string('saverecording', 'assignment_poodllonline'));
							//$mform->addRule(FILENAMECONTROL, get_string('required'), 'required', null, 'client');
							$mform->addElement('static', 'description', '',$mediadata);
							//$mform->addElement('static', 'description', get_string('videorecorder', 'assignment_poodllonline'),$mediadata);
							//we don't give option to write text, so break here							
						}else{
							//It should be already displayed
							//at the top of the submission area
							//$mediadata= format_text('{FMS:VIDEO='.	$this->_customdata['mediapath'] .'}', FORMAT_HTML);							
							//$mform->addElement('static', 'description', get_string('videorecorder', 'assignment_poodllonline'),$mediadata);
						}
						break;
					
									
				}
				
				//If we are recording text, and we do not need torecord media first
				//We display the text box
				switch ($this->_customdata['assignment']->var3){
						
						case OM_REPLYVIDEOTHENTEXT:
						case OM_REPLYVOICETHENTEXT:
							if (empty($this->_customdata['mediapath'])){
								break;
							}
						case OM_REPLYVOICEONLY:	
						case OM_REPLYVIDEOONLY:
						case OM_REPLYTALKBACK:
							//We do not need a text box
							break;
						case OM_REPLYTEXTONLY:							
						default:
								$mediadata="";

								// visible elements
								//$mform->addElement('editor', 'text', get_string('submission', 'assignment'), array('cols'=>85, 'rows'=>30));
								$mform->addElement('editor', 'text_editor', get_string('submission', 'assignment'), null, $this->_customdata['editoroptions']);
								//$mform->addElement('editor', 'text', get_string('submission', 'assignment'));
								$mform->setType('text_editor', PARAM_RAW); // to be cleaned before display
								$mform->addRule('text_editor', get_string('required'), 'required', null, 'client');							
				}
						
						
		
        // hidden params
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

		
        // buttons
        $this->add_action_buttons();

		$this->set_data($this->_customdata['data']);
    }
}