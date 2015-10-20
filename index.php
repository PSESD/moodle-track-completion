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
if (!function_exists('d')) {
    function d($var)
    {
    	$backtrace = debug_backtrace();
    	echo '<div style="display: block; margin: 5px; padding: 5px; background-color: #fff; border: 1px solid black; z-index: 999999999; position:relative;">';
        echo '<h3 style="font-size: 14px; margin: 3px">'.$backtrace[0]['file'] .':'. $backtrace[1]['function'].':'. $backtrace[0]['line'].'</h3>';
        $backtrace = array_slice($backtrace, 1, 4);
        foreach ($backtrace as $bt) {
            if (!isset($bt['file'])) { continue; }
            echo '<div style="font-size: 12px; margin: 1px">'.$bt['file'] .':'. $bt['function'].':'. $bt['line'].'</div>';
        }
        echo '<hr />';
        echo '<pre>';
        echo var_dump($var);
        echo '</pre>';
        echo '</div>';
    }
}
/**
 * Config changes report
 *
 * @package    report
 * @subpackage certificatecompletion
 * @copyright  2009 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

ini_set('memory_limit', -1);

require(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/mod/certificate/lib.php');

require_once($CFG->dirroot.'/enrol/externallib.php');
require_once($CFG->dirroot . "/user/profile/lib.php"); // Custom field library.
require_once($CFG->dirroot . "/lib/filelib.php");      // File handling on description and friends.

require_once(dirname(__FILE__) . '/classes/form.php');


require_login();
admin_externalpage_setup('reportgroupcertificatecompletion', '', null, '', array('pagelayout'=>'report'));

$baseurl = new moodle_url('/report/groupcertificatecompletion/index.php');
$mform = new report_groupcertificatecompletion\filter_form($baseurl, array());
$download = optional_param('download', false, PARAM_SAFEDIR);
$group = optional_param('group', false, PARAM_SAFEDIR);

$startDate = false;
$endDate = false;
$data = $mform->get_data();
if ($data) {
    $startDate = $data->start_date;
    $endDate = $data->end_date;
}

if ($download) {
	\report_groupcertificatecompletion\event\report_viewed::create(array('other' => array('group' => $group, 'start_date' => $startDate, 'end_date' => $endDate)))->trigger();
	$reporter = new \report_groupcertificatecompletion\extract($group, $startDate, $endDate);
	$reporter->serveFile();
	exit(0);
}
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', 'report_groupcertificatecompletion'));
echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter centerpara');
$mform->display();
if ($mform->is_cancelled()) {
    // Redirect to course view page if form is cancelled.
    redirect('/');
} else if ($data) {
    \report_groupcertificatecompletion\event\report_viewed::create(array('other' => array('group' => $group, 'start_date' => $startDate, 'end_date' => $endDate)))->trigger();
    $reporter = new \report_groupcertificatecompletion\extract($group, $startDate, $endDate);
    $reporter->serveTable();
}

echo $OUTPUT->box_end();


echo $OUTPUT->footer();