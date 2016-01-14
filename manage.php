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
 * Manage attendance sessions
 *
 * @package    mod_attendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$pageparams = new att_manage_page_params();

$id                         = required_param('id', PARAM_INT);
$from                       = optional_param('from', null, PARAM_ALPHANUMEXT);
$pageparams->view           = optional_param('view', null, PARAM_INT);
$pageparams->curdate        = optional_param('curdate', null, PARAM_INT);
$pageparams->perpage        = get_config('attendance', 'resultsperpage');

$cm             = get_coursemodule_from_id('attendance', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$att            = $DB->get_record('attendance', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$capabilities = array(
    'mod/attendance:manageattendances',
    'mod/attendance:takeattendances',
    'mod/attendance:changeattendances'
);
if (!has_any_capability($capabilities, $context)) {
    redirect($att->url_view());
}

$pageparams->init($cm);
$att = new attendance($att, $cm, $course, $context, $pageparams);

// If teacher is coming from block, then check for a session exists for today.
if ($from === 'block') {
    $sessions = $att->get_today_sessions();
    $size = count($sessions);
    if ($size == 1) {
        $sess = reset($sessions);
        $nottaken = !$sess->lasttaken && has_capability('mod/attendance:takeattendances', $context);
        $canchange = $sess->lasttaken && has_capability('mod/attendance:changeattendances', $context);
        if ($nottaken || $canchange) {
            redirect($att->url_take(array('sessionid' => $sess->id, 'grouptype' => $sess->groupid)));
        }
    } else if ($size > 1) {
        $att->curdate = $today;
        // Temporarily set $view for single access to page from block.
        $att->view = ATT_VIEW_DAYS;
    }
}

$PAGE->set_url($att->url_manage());
$PAGE->set_title($course->shortname. ": ".$att->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(true);
$PAGE->set_button($OUTPUT->update_module_button($cm->id, 'attendance'));
$PAGE->navbar->add($att->name);

$output = $PAGE->get_renderer('mod_attendance');
$tabs = new attendance_tabs($att, attendance_tabs::TAB_SESSIONS);
$filtercontrols = new attendance_filter_controls($att);
$sesstable = new attendance_manage_data($att);

// Output starts here.

echo $output->header();
echo $output->heading(get_string('attendanceforthecourse', 'attendance').' :: ' .format_string($course->fullname));
mod_attendance_notifyqueue::show();
echo $output->render($tabs);
echo $output->render($filtercontrols);
echo $output->render($sesstable);

echo $output->footer();
?>
<script>
$('table').find('tr').each(function(){
		$(this).find('th').eq(4).after('<th class="header c7" style="text-align:center;width:*;" scope="col">QR Code</th>');
		$(this).find('td').eq(4).after('<td class="cell c7" style="width:1px;"><img src="pix/qr-icon.png" onclick="showModal()" /></td>');
});
</script>

<script>

function showModal(title, body) {
	var title = "Edit QR Code Print Page";
	var body = "Course: <?php echo $course->fullname; ?> " ;
    var html = '<div class="modal fade" tabindex="-1" role="dialog">' +
    '<div class="modal-dialog">' +
    '<div class="modal-content">' +
    '<div class="modal-header">' +
    '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
    '<h4 class="modal-title">' + title + '</h4>' +
    '</div>' +
    '<div class="modal-body">' +
    '<p>' + body + '</p>' +
    '</div>' +
    '<div class="modal-footer">' + 
    '<center><button type="button" class="btn btn-primary">Save & Print</button>' +
    '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button></center>' +
    '</div>' +
    '</div>' +
    '</div>' +
    '</div>';

    var modal = $(html);
    modal.modal("show");
}
</script>
