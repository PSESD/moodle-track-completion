<?php
namespace report_groupcertificatecompletion;

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->dirroot . "/user/lib.php");

/*
	Author: Jacob Morrison (jmorrison@psesd.org)
*/
class extract extends object {
	public $group;
	public $startDate;
	public $endDate;
	public $error = false;
	public $users = false;
	protected $_file;
	protected $_filePath;

	public function __construct($group, $startDate, $endDate)
	{
		$this->group = $group;
		$this->startDate = $startDate;
		$this->endDate = $endDate;
		register_shutdown_function([$this, 'cleanFile']);
	}

	protected function prepare()
	{
		global $DB;
		$courseCategory = get_config('report_groupcertificatecompletion', 'category');
		$courses = array();
		if ($courseCategory) {
			$categoryCoursesRaw = $DB->get_records('course', array('category' => unserialize($courseCategory)));
			foreach ($categoryCoursesRaw as $course) {
				$courses[] = $course->id;
			}
		}
		if (empty($courses)) {
			return false;
		}
		$groupExtra = 'g.courseid IN (\''.implode("', '", $courses) .'\')';
		if (!empty($this->group)) {
			$groupExtra .= ' AND g.enrolmentkey="'.$this->group.'"';
		}
		$sql = 'SELECT user.id as user_id, g.name as group_name, g.id as group_id, g.courseid as course_id 
			FROM {user} user
			INNER JOIN {groups_members} gm ON (gm.userid = user.id)
			INNER JOIN {groups} g ON (gm.groupid = g.id)
			WHERE '.$groupExtra.'
			AND gm.timeadded >= '.$this->startDate.'
			AND gm.timeadded <= '.$this->endDate.' 
			GROUP BY user.id
			ORDER BY user.lastname ASC, user.firstname ASC';
		$records = $DB->get_records_sql($sql);
		$groupMap = array();
		foreach ($records as $record) {
			$groupMap[$record->user_id] = array('id' => $record->group_id, 'name' => $record->group_name, 'course' => $record->course_id);
		}
		objects\user::load($groupMap);
	}

	protected function rollCsv()
	{
		$columns = [];
		$columns[] = ['label' => 'First Name', 'value' => function($user) { return $user->getMetaField('firstname'); }];
		$columns[] = ['label' => 'Last Name', 'value' => function($user) { return $user->getMetaField('lastname'); }];
		$columns[] = ['label' => 'Username', 'value' => function($user) { return $user->getMetaField('username'); }];
		$columns[] = ['label' => 'Email', 'value' => function($user) { return $user->getMetaField('email'); }];
		$columns[] = ['label' => 'Site Name', 'value' => function($user) { return $user->getMetaField('sitename'); }];
		$columns[] = ['label' => 'Program', 'value' => function($user) { return $user->getMetaField('program'); }];
		$columns[] = ['label' => 'Stars ID', 'value' => function($user) { return $user->getMetaField('starsid'); }];
		$columns[] = ['label' => 'Group', 'value' => function($user) { return $user->getMetaField('groupname'); }];
		
		$courses = get_config('report_groupcertificatecompletion', 'courses');
		if (!empty($courses) && ($courses = unserialize($courses))) {
			foreach ($courses as $courseId) {
				$course = \get_course($courseId);
				if (empty($course)) { continue; }
				$columns[] = ['label' => $course->shortname, 'value' => function($user) use ($courseId) { return $user->courseCompletionDate($courseId); }];
			}
		}

		$columns[] = ['label' => 'Name of Track', 'value' => function($user) { return $user->getTrackName(); }];
		$columns[] = ['label' => 'Total Number of Courses in Track', 'value' => function($user) { return $user->getNumberTrackCourses(); }];
		$columns[] = ['label' => 'Number of track courses completed by Student', 'value' => function($user) { return $user->getNumberTrackCoursesCompleted(); }];
		$columns[] = ['label' => 'Course Track Completion Date', 'value' => function($user) { return $user->courseTrackCompletionDate(); }];

		$columnLabels = [];
		foreach ($columns as $column) {
			$columnLabels[] = $this->cleanValue($column['label']);
		}
		fputcsv($this->file, $columnLabels);

		foreach (objects\user::getAll() as $user) {
			$row = [];
			foreach ($columns as $column) {
				$row[] = $this->cleanValue($column['value']($user));
			}
			fputcsv($this->file, $row);
		}
	}

	public function cleanValue($value)
	{
		$value = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $value);
		$value = htmlspecialchars_decode($value);
		return $value;
	}

	public function serveFile()
	{
		$file = $this->getFile();
		$this->prepare();
		$this->rollCsv();

		fclose($file);
		if ($this->error || !file_exists($this->getFilePath())) {
			throw new \Exception("Creation of report failed (".$this->error.").");
		}
		\send_file($this->getFilePath(), 'certificate_completion_'.date("Y-m-d") .'.csv', 'default' , 0, false, true, 'application/csv');
	}

	public function serveTable()
	{
		$file = $this->getFile();
		$this->prepare();

		$columns = [];
		$columns[] = ['label' => 'User', 'value' => function($user) { return '<a href="/user/view.php?id=' . $user->id . '">'. $user->getMetaField('firstname') .' '. $user->getMetaField('lastname') .'</a>'; }];
		// $columns[] = ['label' => 'Last Name', 'value' => function($user) { return $user->getMetaField('lastname'); }];
		// $columns[] = ['label' => 'Username', 'value' => function($user) { return $user->getMetaField('username'); }];
		// $columns[] = ['label' => 'Email', 'value' => function($user) { return $user->getMetaField('email'); }];
		$columns[] = ['label' => 'Site Name', 'value' => function($user) { return $user->getMetaField('sitename'); }];
		// $columns[] = ['label' => 'Program', 'value' => function($user) { return $user->getMetaField('program'); }];
		// $columns[] = ['label' => 'Stars ID', 'value' => function($user) { return $user->getMetaField('starsid'); }];
		$columns[] = ['label' => 'Group', 'value' => function($user) { return $user->getMetaField('groupname'); }];

		$courses = get_config('report_groupcertificatecompletion', 'courses');
		if (!empty($courses) && ($courses = unserialize($courses))) {
			foreach ($courses as $courseId) {
				$course = \get_course($courseId);
				if (empty($course)) { continue; }
				$columns[] = ['label' => $course->shortname, 'value' => function($user) use ($courseId) { return $user->courseCompletionDate($courseId); }];
			}
		}

		$columns[] = ['label' => 'Name of Track', 'value' => function($user) { return $user->getTrackName(); }];
		$columns[] = ['label' => 'Total Number of Courses in Track', 'value' => function($user) { return $user->getNumberTrackCourses(); }];
		$columns[] = ['label' => 'Number of track courses completed by Student', 'value' => function($user) { return $user->getNumberTrackCoursesCompleted(); }];
		$columns[] = ['label' => 'Course Track Completion Date', 'value' => function($user) { return $user->courseTrackCompletionDate(); }];


		$columnLabels = [];
		foreach ($columns as $column) {
			$columnLabels[] = $this->cleanValue($column['label']);
		}

		$table = new \html_table();
		$table->head = $columnLabels;
		foreach (objects\user::getAll() as $user) {
			$row = [];
			foreach ($columns as $column) {
				$row[] = $this->cleanValue($column['value']($user));
			}
			$table->data[] = $row;
		}
		echo \html_writer::table($table);
	}

	public function cleanFile()
	{
		@unlink($this->getFilePath());
	}

	public function getFile()
	{
		if (!isset($this->_file)) {
			$this->_file = fopen($this->getFilePath(), 'w');
			if (!$this->_file) {
				throw new \Exception("Unable to open temp file for writing. Please check settings.");
			}
		}
		return $this->_file;
	}

	public function getFilePath() {
	    global $CFG;
	    if (isset($this->_filePath)) {
	    	return $this->_filePath;
	    }
	    $path = [];
	    $path[] = $CFG->dataroot;
	    $path[] = $dataPath = 'admin_report_groupcertificatecompletiontemp';
	    $path[] = date("Y-m-d_H-i-s") .'-'. $this->group .'.csv';
	    \make_upload_directory($dataPath);
	    return $this->_filePath = implode(DIRECTORY_SEPARATOR, $path);
	}

	public function getCacheFilePath() {
	    global $CFG;
	    if (isset($this->_cacheFilePath)) {
	    	return $this->_cacheFilePath;
	    }
	    $path = [];
	    $path[] = $CFG->dataroot;
	    $path[] = $dataPath = 'admin_report_groupcertificatecompletiontemp';
	    $path[] = 'data_cache.csv';
	    \make_upload_directory($dataPath);
	    return $this->_cacheFilePath = implode(DIRECTORY_SEPARATOR, $path);
	}
}