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
    const MAX_NAME_LENGTH = 60;
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

    public static function getCategories()
    {
        global $DB;
        $categories = $DB->get_records_sql('SELECT id, name FROM {course_categories} ORDER BY name ASC');
        $categorySelect = [];
        foreach($categories as $cat) {
            $name = $cat->name;
            if (strlen($name) > static::MAX_NAME_LENGTH) {
                $name = substr($name, 0, static::MAX_NAME_LENGTH) .'...';
            }
            $categorySelect[$cat->id] = $name;
        }
        return $categorySelect;
    }

    public static function getCourses()
    {
        global $DB;
        $courses = $DB->get_records_sql('SELECT id, shortname FROM {course} ORDER BY shortname ASC');
        $courseSelect = [];
        foreach($courses as $course) {
            $name = $course->shortname;
            if (strlen($name) > static::MAX_NAME_LENGTH) {
                $name = substr($name, 0, static::MAX_NAME_LENGTH) .'...';
            }
            $courseSelect[$course->id] = $name;
        }
        return $courseSelect;
    }

    /**
     * @see lib/moodleform#definition()
     */
    public function definition() {
        global $CFG, $COURSE, $DB, $PAGE, $cm;
        $mform = $this->_form;
        
        // Add action button to the top of the form.
        $addactionbuttons = false;
        $context = \context_system::instance();
        if (has_capability('moodle/site:config', $context)) {
            // Admin
            $mform->addElement('header', 'grouphead', get_string('admin_settings', 'report_groupcertificatecompletion'));
            $mform->setExpanded('grouphead', false);
            $mform->addElement('select', 'config_category', get_string('admin_choose_category', 'report_groupcertificatecompletion'), static::getCategories());
            $mform->addElement('select', 'config_courses', get_string('admin_choose_courses', 'report_groupcertificatecompletion'), static::getCourses(), array('multiple' => true));
            $mform->setDefault('config_category', unserialize(get_config('report_groupcertificatecompletion', 'category')));
            $mform->setDefault('config_courses', unserialize(get_config('report_groupcertificatecompletion', 'courses')));
        }

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
