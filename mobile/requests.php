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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 *
 * @package mod
 * @subpackage attendance
 * @copyright
 *
 * @license
 *
 */
// define('AJAX_SCRIPT', true);
// define('NO_DEBUG_DISPLAY', true);
require_once (dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . '/config.php');
require_once $CFG->dirroot . '/mod/attendance/mobile/locallib.php';
require_once $CFG->libdir . '/accesslib.php';
global $CFG, $DB, $OUTPUT, $PAGE, $USER;

$action = required_param ( 'action', PARAM_ALPHA );
$username = required_param ( 'username', PARAM_RAW_TRIMMED );
$password = required_param ( 'password', PARAM_RAW_TRIMMED );

if (! $user = authenticate_user_login ( $username, $password )) {
	attendance_json_error ( 'Invalid username or password' );
}
// This is the correct way to fill up $USER variable
// complete_user_login($user);

switch ($action) {
	
	case 'login':
		if (! $user ) {
			attendance_json_error ( 'Invalid username or password' );
		}
		else{
			attendance_json_error ( 'Valid login' );
		}
		break;
	
	
	case 'sessions' :
		$sqlgetsessions = "SELECT sess.id AS sessionid, course.fullname AS coursename, course.id AS courseid,
							sess.description AS description, FROM_UNIXTIME(sess.sessdate) AS time 
							FROM  {attendance_sessions} AS sess
							INNER JOIN {attendance} AS att ON (att.id= sess.attendanceid )
							INNER JOIN {course} AS course ON ( course.id = att.course )
							WHERE
							course.id IN 
									(SELECT course FROM 
									(SELECT c.id AS course
									FROM {user} AS u
									INNER JOIN {role_assignments} AS ra ON (ra.userid = u.id)
									INNER JOIN {context} AS ct ON (ct.id = ra.contextid)
									INNER JOIN {course} AS c ON (c.id = ct.instanceid)
									INNER JOIN {role} AS r ON (r.id = ra.roleid)	
									WHERE u.id= ? ) as courses)
							AND 
							sess.id NOT IN
								( SELECT takensessions FROM (
									SELECT sess.id AS takensessions FROM mdl_attendance_log  AS log
									INNER JOIN mdl_user AS users ON ( users.id = log.studentid )
									INNER JOIN mdl_attendance_sessions AS sess ON (sess.id = log.sessionid)
									INNER JOIN mdl_attendance AS att ON (att.id= sess.attendanceid )
									INNER JOIN mdl_course AS course ON ( course.id = att.course )
									WHERE users.id = ? ) AS taken)
							AND FROM_UNIXTIME(sess.sessdate) >= NOW()
							ORDER BY FROM_UNIXTIME(sess.sessdate) ASC
				";
		//missing DateADD in case you want to take attendance within a margin of time
		
		$sessions = $DB->get_recordset_sql ( $sqlgetsessions, array (
				$user->id,
				$user->id 
		) );
		//var_dump($sessions);
		if (! $sessions) {
			$output = array ();
			$output [] = 0;
		} else {
			foreach ( $sessions as $obj ) {
				$output [] = $obj;
			}
		}
		
		echo attendance_json_array($output);
		break;
}//end of actions
	
	
	
	
