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



namespace report_groupcertificatecompletion;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/grouplib.php');


class filter_form extends \moodleform {

    public static function getGroups()
    {
        global $DB;
        $groups = $DB->get_records_sql('SELECT id, name, enrolmentkey FROM {groups}  WHERE enrolmentkey != "" GROUP BY name, enrolmentkey');
        $groupsSelect = [];
        foreach($groups as $group) {
            $groupsSelect[$group->enrolmentkey] = $group->name . ' ('.$group->enrolmentkey.')';
        }
        return $groupsSelect;
    }

    /**
     * @see lib/moodleform#definition()
     */
    public function definition() {
        global $CFG, $COURSE, $DB, $PAGE;
        $mform = $this->_form;
        
        // Add action button to the top of the form.
        $addactionbuttons = false;

        // Group
        $mform->addElement('header', 'grouphead', get_string('choose_group', 'report_groupcertificatecompletion'));
        $mform->setExpanded('grouphead', true);
        $groups = array_merge(['' => ''], static::getGroups());
        $mform->addElement('select', 'group', 'Group', $groups);


        // Date Range
        $mform->addElement('header', 'daterangehead', get_string('choose_date_range', 'report_groupcertificatecompletion'));
        $mform->setExpanded('daterangehead', true);
        $mform->addElement('date_selector', 'start_date', 'Start Date');
        $mform->addElement('date_selector', 'end_date', 'End Date');
        $mform->setDefault('start_date', strtotime("1 year ago"));
        $mform->setDefault('end_date', time());
        
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string("display", 'report_groupcertificatecompletion'));
        $buttonarray[] = &$mform->createElement('submit', 'download', get_string("download_csv", 'report_groupcertificatecompletion'));
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
