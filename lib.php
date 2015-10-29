<?php



function report_trackcompletion_extend_navigation_course($navigation, $course, $context) {
    global $CFG, $OUTPUT, $PAGE;
	$context = \context_system::instance();
	// var_dump("hey");exit;
	if (has_capability('report/trackcompletion:view', $context)) {
        $url = new moodle_url('/report/trackcompletion/index.php');
	    $navigation->add(get_string('pluginname', 'report_trackcompletion'), $url);
	    //echo "done";exit;
	}
}