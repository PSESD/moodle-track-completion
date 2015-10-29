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



namespace report_trackcompletion;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/grouplib.php');


class filter_form extends \moodleform {
    const MAX_NAME_LENGTH = 60;
    public static function getGroups()
    {
        global $DB;
        global $USER;

        static $groupsSelect;
        if (!isset($gropSelect)) {
            $enrolmentFilter = false;
            $context = \context_system::instance();
            if (!has_capability('moodle/site:config', $context)) {
                $enrolmentFilter = array();
                if (!empty($USER->profile['EnrollmentCodeScope'])) {
                    $enrolmentFilter = explode(',', $USER->profile['EnrollmentCodeScope']);
                }
            }
            $groups = $DB->get_records_sql('SELECT id, name, enrolmentkey FROM {groups}  WHERE enrolmentkey != "" GROUP BY name, enrolmentkey');
            $groupsSelect = [];
            foreach($groups as $group) {
                if ($enrolmentFilter === false || in_array($group->enrolmentkey, $enrolmentFilter)) {
                    $groupsSelect[$group->enrolmentkey] = $group->name . ' ('.$group->enrolmentkey.')';
                }
            }
        }
        return $groupsSelect;
    }

    /**
     * @see lib/moodleform#definition()
     */
    public function definition() {
        global $CFG, $COURSE, $DB, $PAGE, $cm;
        $mform = $this->_form;
        
        // Add action button to the top of the form.
        $addactionbuttons = false;

        // Group
        $mform->addElement('header', 'grouphead', get_string('choose_group', 'report_trackcompletion'));
        $mform->setExpanded('grouphead', true);
        $groups = static::getGroups();
        $context = \context_system::instance();
        if (has_capability('moodle/site:config', $context)) {
            $groups = array_merge(['' => ''], $groups);
        }
        $mform->addElement('select', 'group', 'Group', $groups);


        // Date Range
        $mform->addElement('header', 'daterangehead', get_string('choose_date_range', 'report_trackcompletion'));
        $mform->setExpanded('daterangehead', true);
        $mform->addElement('date_selector', 'start_date', 'Start Date');
        $mform->addElement('date_selector', 'end_date', 'End Date');
        $default_start_date = get_config('report_trackcompletion', 'default_start_date');
        if (empty($default_start_date)) {
            $default_start_date = time();
        } else {
            $default_start_date = unserialize($default_start_date);
        }
        $mform->setDefault('start_date', $default_start_date);
        $mform->setDefault('end_date', time());
        
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string("display", 'report_trackcompletion'));
        $buttonarray[] = &$mform->createElement('submit', 'download', get_string("download_csv", 'report_trackcompletion'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function validation($data, $files) {
        global $CFG;
        $errors = parent::validation($data, $files);
        // if (empty($data['group'])) {
        //     $errors['group'] = 'Field is required';
        // }
        return $errors;
    }
}
