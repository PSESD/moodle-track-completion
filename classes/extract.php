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
	public $records = false;
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
		$groupExtra = '1=1';
		if (!empty($this->group)) {
			$groupExtra = 'g.enrolmentkey="'.$this->group.'"';
		}
		$sql = 'SELECT ci.id as certificate_issue_id, c.id as course_id, c.fullname as course_name, user.id as user_id, user.username as user_username, user.firstname as user_firstname, user.lastname as user_lastname, user.email as user_email, g.name as group_name, ci.timecreated as issue_date 
			FROM {certificate_issues} ci
			INNER JOIN {certificate} cert ON (cert.id = ci.certificateid)
			INNER JOIN {course} c ON (cert.course = c.id)
			INNER JOIN {user} user ON (ci.userid = user.id)
			INNER JOIN {groups_members} gm ON (gm.userid = user.id)
			INNER JOIN {groups} g ON (gm.groupid = g.id)
			WHERE '.$groupExtra.'
			AND ci.timecreated >= '.$this->startDate.'
			AND ci.timecreated <= '.$this->endDate.' 
			GROUP BY ci.id
			ORDER BY user.lastname ASC, user.firstname ASC, c.fullname ASC';
		$records = $DB->get_records_sql($sql);
		$this->records = $records;
	}

	protected function rollCsv()
	{
		$columns = [];
		$columns[] = ['label' => 'First Name', 'value' => function($record) { return $record->user_firstname; }];
		$columns[] = ['label' => 'Last Name', 'value' => function($record) { return $record->user_lastname; }];
		$columns[] = ['label' => 'Username', 'value' => function($record) { return $record->user_username; }];
		$columns[] = ['label' => 'Email', 'value' => function($record) { return $record->user_email; }];
		$columns[] = ['label' => 'Course ID', 'value' => function($record) { return $record->course_id; }];
		$columns[] = ['label' => 'Course Title', 'value' => function($record) { return $record->course_name; }];
		$columns[] = ['label' => 'Group', 'value' => function($record) { return $record->group_name; }];
		$columns[] = ['label' => 'Certificate Date', 'value' => function($record) { return date("Y-m-d", $record->issue_date); }];

		$columnLabels = [];
		foreach ($columns as $column) {
			$columnLabels[] = $this->cleanValue($column['label']);
		}
		fputcsv($this->file, $columnLabels);

		foreach ($this->records as $record) {
			$row = [];
			foreach ($columns as $column) {
				$row[] = $this->cleanValue($column['value']($record));
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

		$table = new \html_table();
		$table->head = array('Student', 'Course', 'Group', 'Completion Date');
		foreach ($this->records as $record) {
		    $item = [];
		    $item[] = '<a href="/user/view.php?id='.$record->user_id.'">'.$record->user_firstname.' '.$record->user_lastname.'</a>';
		    $item[] = '<a href="/course/view.php?id='.$record->course_id.'">'.$record->course_name.'</a>';
			$item[] = $record->group_name;
			$item[] = date("M d, Y", $record->issue_date);
		    $table->data[] = $item;
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