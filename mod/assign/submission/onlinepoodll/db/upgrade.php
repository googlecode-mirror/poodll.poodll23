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
 * Upgrade code for install
 *
 * @package    assignsubmission_onlinepoodll
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Stub for upgrade code
 * @param int $oldversion
 * @return bool
 */
function xmldb_assignsubmission_onlinepoodll_upgrade($oldversion) {
	 global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Moodle v2.3.0 release upgrade line
    // Put any upgrade step following this
	    // Moodle v2.3.0 release upgrade line
    // Put any upgrade step following this
    
	//Change table name to satisfy Moodle.org plugin submissions component name v table name check
	//ie it checks(or will) check 1st 28 chars of component name matches table name.
    if ($oldversion < 2012112000) {
    	$table = new xmldb_table('assignsubmission_onlinepood');	
		if ($dbman->table_exists($table)){
			$dbman->rename_table( $table, 'assignsubmission_onlinepoodl', $continue=true, $feedback=true);   
        }
		 // online PoodLL savepoint reached
        upgrade_plugin_savepoint(true, 2012112000, 'assignsubmission', 'onlinepoodll');
    
    }
	
	//add filename field.
    if ($oldversion < 2013120500) {
    	$table = new xmldb_table('assignsubmission_onlinepoodl');	
		$table->add_field('filename', XMLDB_TYPE_TEXT, 'small', null,
                null, null, null);

		
		 // online PoodLL savepoint reached
        upgrade_plugin_savepoint(true, 2013120500, 'assignsubmission', 'onlinepoodll');
    
    }

    return true;
}


