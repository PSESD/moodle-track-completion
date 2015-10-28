<?php
namespace report_groupcertificatecompletion\objects;

class user extends base {
	protected $_track = false;
	protected $_loaded = false;
	protected $_completed_courses = [];

	public static function load($groupMap)
	{
		global $CFG;
		$user = new static;
		$usersRaw = $user->db->get_records_sql('SELECT * FROM {user} WHERE id IN (\''.implode("', '", array_keys($groupMap)).'\')');
		foreach ($usersRaw as $userRaw) {
			$fields = $user->db->get_recordset_sql("SELECT f.shortname, d.data
		                                        FROM {user_info_field} f
		                                        JOIN {user_info_data} d ON (f.id=d.fieldid)
		                                    WHERE d.userid=". $userRaw->id);
		    $userRaw->customfields = [];
		    foreach ($fields as $field) {
		    	 $userRaw->customfields[$field->shortname] = $field->data;
		    }
		    $userRaw->customfields['groupid'] = $groupMap[$userRaw->id]['id'];
		    $userRaw->customfields['groupname'] = $groupMap[$userRaw->id]['name'];
		    $userRaw->customfields['trackcourse'] = $groupMap[$userRaw->id]['course'];
		    static::loadOne($userRaw);
		    $fields->close();
		}
	}

	public function getTotalCompletedCourses()
	{
		return count($this->_completed_courses);
	}

	public function postLoad()
	{
		if (!$this->_loaded) {
			if ($courseCompletions = $this->db->get_records('course_completions', array('userid' => $this->id))) {
				foreach ($courseCompletions as $completionRaw) {
					if (empty($completionRaw->timecompleted)) { continue; }
					$this->_completed_courses[$completionRaw->course] = (int) $completionRaw->timecompleted;
				}
			}
			$track = track::load($this->meta['trackcourse']);
			if ($track) {
				$this->_track = $track;
			}
			$this->_loaded = true;
		}
		return parent::postLoad();
	}

	public static function loadOne($userRaw)
	{
		$userParams = [];
		$userParams['id'] = $userRaw->id;
		$userParams['username'] = $userRaw->username;
		$userParams['firstname'] = $userRaw->firstname;
		$userParams['lastname'] = $userRaw->lastname;
		$userParams['email'] = $userRaw->email;
		$userParams['deleted'] = $userRaw->deleted;

		$customParams = ['SiteName' => 'sitename', 'Program' => 'program', 'starsid' => 'starsid', 'groupname' => 'groupname', 'groupid' => 'groupid', 'trackcourse' => 'trackcourse'];
		foreach ($customParams as $param => $id) {
			$userParams[$id] = null;
		}
		foreach ($userRaw->customfields as $name => $value) {
			if (!isset($customParams[$name])) { continue; }
			$key = $customParams[$name];
			$userParams[$key] = $value;
		}
		$user = static::loadObject($userParams);
		return $user;
	}

	public function getCompletedCourses()
	{
		$completed = array();
		if (isset($this->_track)) {
			foreach ($this->_track->courses as $course) {
				if (isset($this->_completed_courses[$course])) {
					$completed[$course] = $this->_completed_courses[$course];
				}
			}
		}
		return $completed;
	}

	public function courseCompletionDate($courseId)
	{
		if (isset($this->_completed_courses[$courseId])) {
			return date("m/d/Y", $this->_completed_courses[$courseId]);
		}
		return null;
	}

	public function hasCompletedTrack()
	{
		$completed = false;
		if (isset($this->_track)) {
			$completed = true;
			foreach ($this->_track->courses as $course) {
				if (!isset($this->_completed_courses[$course])) {
					$completed = false;
				}
			}
		}
		return $completed;
	}

	public function courseTrackCompletionDate()
	{
		if ($this->hasCompletedTrack()) {
			$completed = $this->getCompletedCourses();
			$largest = false;
			foreach ($completed as $id => $datetime) {
				if (!$largest || $datetime > $largest) {
					$largest = $datetime;
				}
			}
			if ($largest) {
				return date("m/d/Y", $largest);
			}
		}
		return null;
	}

	public function getNumberTrackCourses()
	{
		if (isset($this->_track)) {
			return count($this->_track->courses);
		}
		return null;
	}

	public function getNumberTrackCoursesCompleted()
	{
		$completed = $this->getCompletedCourses();
		if ($completed !== null) {
			return count($completed);
		}
		return null;
	}

	public function getTrackName()
	{
		if (isset($this->_track)) {
			return $this->_track->getMetaField('shortname');
		}
		return null;
	}
	public function getIsValid()
	{
		if (empty($this->_track)) {
			return false;
		}
		if (!empty($this->meta['deleted'])) {
			return false;
		}
		return true;
	}

	public function getCourses()
	{
		return $this->_courses;
	}

	public function enrollIn($course) {
		$this->_courses[$course->id] = $course;
	}

	public function addCertificateIssue($certificateIssue)
	{
		$this->_certificate_issues[$certificateIssue->id] = $certificateIssue;
	}

	public function getCertificateIssues()
	{
		return $this->_certificate_issues;
	}
}
?>