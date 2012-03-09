<?php  //$Id: upgrade.php,v 1.7.2.5 2008/05/01 20:37:22 skodak Exp $

// This file keeps track of upgrades to
// the assignment module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_assignment_poodllonline_upgrade($oldversion=0) {

    /*
	global $CFG, $THEME;
	$dbman = $DB->get_manager();
	
    if ($oldversion < 2010030312) {
        // add field to store media comments (audio or video) filename to students submissions
        $sql= "ALTER TABLE {assignment_submissions} ADD poodllfeedback TEXT";
		$result = $DB->execute_sql($sql);
    }
*/

    return true;
}
