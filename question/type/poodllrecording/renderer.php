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
 * PoodLLrecording question renderer class.
 *
 * @package    qtype
 * @subpackage poodllrecording
 * @copyright  2012 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/poodllresourcelib.php');
require_once($CFG->libdir . '/poodllfilelib.php');

/**
 * Generates the output for poodllrecording questions.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_poodllrecording_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $question = $qa->get_question();
        $responseoutput = $question->get_format_renderer($this->page);

        // Answer field.
        $step = $qa->get_last_step_with_qt_var('answer');
        if (empty($options->readonly)) {
            $answer = $responseoutput->response_area_input('answer', $qa,
                    $step, $question->responsefieldlines, $options->context);

        } else {
            $answer = $responseoutput->response_area_read_only('answer', $qa,
                    $step, $question->responsefieldlines, $options->context);
        }

        $files = '';
        if ($question->attachments) {
            if (empty($options->readonly)) {
                $files = $this->files_input($qa, $question->attachments, $options);

            } else {
                $files = $this->files_read_only($qa, $options);
            }
        }
		
        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext($qa),
                array('class' => 'qtext'));

        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
        $result .= html_writer::tag('div', $answer, array('class' => 'answer'));
        $result .= html_writer::tag('div', $files, array('class' => 'attachments'));
        $result .= html_writer::end_tag('div');

        return $result;
    }

    /**
     * Displays any attached files when the question is in read-only mode.
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_read_only(question_attempt $qa, question_display_options $options) {
        $files = $qa->get_last_qt_files('attachments', $options->context->id);
        $output = array();

        foreach ($files as $file) {
            $mimetype = $file->get_mimetype();
            $output[] = html_writer::tag('p', html_writer::link($qa->get_response_file_url($file),
                    $this->output->pix_icon(file_mimetype_icon($mimetype), $mimetype,
                    'moodle', array('class' => 'icon')) . ' ' . s($file->get_filename())));
        }
        return implode($output);
    }

  
    public function manual_comment(question_attempt $qa, question_display_options $options) {
        if ($options->manualcomment != question_display_options::EDITABLE) {
            return '';
        }

        $question = $qa->get_question();
        return html_writer::nonempty_tag('div', $question->format_text(
                $question->graderinfo, $question->graderinfo, $qa, 'qtype_poodllrecording',
                'graderinfo', $question->id), array('class' => 'graderinfo'));
    }
}


/**
 * A base class to abstract out the differences between different type of
 * response format.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_poodllrecording_format_renderer_base extends plugin_renderer_base {
    /**
     * Render the students respone when the question is in read-only mode.
     * @param string $name the variable name this input edits.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param int $lines approximate size of input box to display.
     * @param object $context the context teh output belongs to.
     * @return string html to display the response.
     */
    public abstract function response_area_read_only($name, question_attempt $qa,
            question_attempt_step $step, $lines, $context);

    /**
     * Render the students respone when the question is in read-only mode.
     * @param string $name the variable name this input edits.
     * @param question_attempt $qa the question attempt being display.
     * @param question_attempt_step $step the current step.
     * @param int $lines approximate size of input box to display.
     * @param object $context the context teh output belongs to.
     * @return string html to display the response for editing.
     */
    public abstract function response_area_input($name, question_attempt $qa,
            question_attempt_step $step, $lines, $context);

    /**
     * @return string specific class name to add to the input element.
     */
    protected abstract function class_name();
}


/**
 * An poodllrecording format renderer for poodllrecordings for audio
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_poodllrecording_format_audio_renderer extends plugin_renderer_base {
   

    protected function class_name() {
        return 'qtype_poodllrecording_audio';
    }
/*
	protected function prepare_response($name, question_attempt $qa,
            question_attempt_step $step, $context) {
        if (!$step->has_qt_var($name)) {
            return '';
        }

        $formatoptions = new stdClass();
        $formatoptions->para = false;
        $text = $qa->rewrite_response_pluginfile_urls($step->get_qt_var($name),
                $context->id, 'answer', $step);
        return format_text($text, $step->get_qt_var($name . 'format'), $formatoptions);
    }
*/
	protected function textarea($response, $lines, $attributes) {
        $attributes['class'] = $this->class_name() . ' qtype_essay_response';
        $attributes['rows'] = $lines;
        $attributes['cols'] = 60;
        return html_writer::tag('textarea', s($response), $attributes);
	}

	//When I swapped this out with prepare_reponse_for_editing_with_text the draftfile.php was replaced with pluginfile.php
	//but no moving files off to better storage was done.
	/*
	 protected function prepare_response_for_editing($name,
            question_attempt_step $step, $context) {
        return array(0, $step->get_qt_var($name));
    }
    */
    
    
       protected function prepare_response_for_editing($name,
            question_attempt_step $step, $context) {
        return $step->prepare_response_files_draft_itemid_with_text(
                $name, $context->id, $step->get_qt_var($name));
                
    }
    
    

    public function response_area_read_only($name, $qa, $step, $lines, $context) {	
    		global $CFG;
   			//fetch file from storage and figure out URL
    		$storedfiles=$qa->get_last_qt_files($name,$context->id);
    		foreach ($storedfiles as $sf){
    			$pathtofile=$qa->get_response_file_url($sf);
    			break;
    		}

			//$pathtofile= $this->prepare_response($name, $qa, $step, $context);
			//return "path:" . $pathtofile ;
			//return "path:" . $pathtofile . "<br />" . fetchSimpleAudioPlayer('swf',$pathtofile,"http",400,25);
			return fetchSimpleAudioPlayer('swf',$pathtofile,"http",400,25);
    }


    public function response_area_input($name, $qa, $step, $lines, $context) {
    	global $USER;
    	$usercontextid=get_context_instance(CONTEXT_USER, $USER->id)->id;
    	
		//prepare a draft file id for use
		list($draftitemid, $response) = $this->prepare_response_for_editing( $name, $step, $context);

		//prepare the tags for our hidden( or shown ) input
		$inputname = $qa->get_qt_field_name($name);
		$inputid =  $inputname . '_id';
		
		//our answerfield
		$ret =	html_writer::empty_tag('input', array('type' => 'hidden','id'=>$inputid, 'name' => $inputname));
		//$ret = $this->textarea($step->get_qt_var($name), $lines, array('name' => $inputname,'id'=>$inputid));
		
		//our answerfield draft id key
		$ret .=	html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $inputname . ':itemid', 'value'=> $draftitemid));
		
		//our answerformat
		$ret .= html_writer::empty_tag('input', array('type' => 'hidden','name' => $inputname . 'format', 'value' => 1));
	
	
		//the context id $context->id here is wrong, so we just use "5" because it works, why is it wrong ..? J 20120214
		return $ret . fetchAudioRecorderForDraft('swf','question',$inputid, $usercontextid ,'user','draft',$draftitemid);
		return $ret;
    }
}

/**
 * An poodllrecording format renderer for poodllrecordings for video
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_poodllrecording_format_video_renderer extends qtype_poodllrecording_format_audio_renderer {
    

    protected function class_name() {
        return 'qtype_poodllrecording_video';
    }

    public function response_area_read_only($name, $qa, $step, $lines, $context) {
				
			//fetch file from storage and figure out URL
    		$storedfiles=$qa->get_last_qt_files($name,$context->id);
    		foreach ($storedfiles as $sf){
    			$pathtofile=$qa->get_response_file_url($sf);
    			break;
    		}

			return fetchSimpleVideoPlayer('swf',$pathtofile,400,380,"http");
	
    }

	

    public function response_area_input($name, $qa, $step, $lines, $context) {
    	global $USER;
    	$usercontextid=get_context_instance(CONTEXT_USER, $USER->id)->id;
    	
		//prepare a draft file id for use
		list($draftitemid, $response) = $this->prepare_response_for_editing( $name, $step, $context);


		$inputname = $qa->get_qt_field_name($name);
		$inputid =  $inputname . '_id';
		
			//our answerfield
		$ret =	html_writer::empty_tag('input', array('type' => 'hidden','id'=>$inputid, 'name' => $inputname));
		//$ret = $this->textarea($step->get_qt_var($name), $lines, array('name' => $inputname,'id'=>$inputid));
		
		//our answerfield draft id key
		$ret .=	html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $inputname . ':itemid', 'value'=> $draftitemid));
		
		$ret .= html_writer::empty_tag('input', array('type' => 'hidden','name' => $inputname . 'format', 'value' => FORMAT_PLAIN));

       
		//the context id $context->id here is wrong, so we just use "5" because it works, why is it wrong ..? J 20120214
		return $ret . fetchVideoRecorderForDraft('swf','question',$inputid, $usercontextid ,'user','draft',$draftitemid);
		return $ret;
		
    }
}

/**
 * An poodllrecording format renderer for poodllrecordings for picture *Not implemented yet Justin 20120214*
 *
 * @copyright  Justin Huny
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_poodllrecording_format_picture_renderer extends qtype_poodllrecording_format_audio_renderer {
    /**
     * @return string the HTML for the textarea.
     */
    protected function textarea($response, $lines, $attributes) {
        $attributes['class'] = $this->class_name() . ' qtype_poodllrecording_response';
        $attributes['rows'] = $lines;
        $attributes['cols'] = 60;
        return html_writer::tag('textarea', s($response), $attributes);
    }

    protected function class_name() {
        return 'qtype_poodllrecording_picture';
    }

    public function response_area_read_only($name, $qa, $step, $lines, $context) {
        return $this->textarea($step->get_qt_var($name), $lines, array('readonly' => 'readonly'));
    }

    public function response_area_input($name, $qa, $step, $lines, $context) {
        $inputname = $qa->get_qt_field_name($name);
        return $this->textarea($step->get_qt_var($name), $lines, array('name' => $inputname)) .
                html_writer::empty_tag('input', array('type' => 'hidden',
                    'name' => $inputname . 'format', 'value' => FORMAT_PLAIN));
    }
}


