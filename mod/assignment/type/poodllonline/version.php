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
 * Version information for the poodllonline assignment type.
 *
 * @package    assignment
 * @subpackage poodllonline
 * @copyright  2012 Justin Hunt 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$plugin->version   = 2013022100;
$plugin->requires  = 2011070100.00;        // Requires this Moodle version
$plugin->component = 'assignment_poodllonline'; 
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = '2.3.9 (Build 2012120400)';
$plugin->dependencies = array('filter_poodll' => 2013021900);
