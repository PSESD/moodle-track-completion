<?php
namespace report_trackcompletion\objects;

class course extends base {
	protected $_certificates = [];
	protected $_users = [];
	public static $loadCount = [];
	protected $_loaded = false;

	public static function load($courseRaw) {
		$course = static::loadObject(['id' => $courseRaw->id, 'shortname' => $courseRaw->shortname, 'fullname' => $courseRaw->fullname]);

		return $course;
	}


	public function getIsValid()
	{
		return !empty($this->_certificates);
	}

	public function postLoad()
	{
		if ($this->_loaded) { return; }
		$this->_loaded = true;

		// load certificates
		$sql = "SELECT c.id, c.name
                  FROM {certificate} c
                 WHERE c.course = :course";
        $params = ['course' => $this->id];
        $certificatesRaw = $this->db->get_records_sql($sql, $params);
        foreach ($certificatesRaw as $certificateRaw) {
        	$certificate = certificate::load($certificateRaw, $this);
        	if ($certificate) {
        		$this->addCertificate($certificate);
        	}
        }
		// static::$loadCount++;
		// $start = microtime(true);
  //       $usersRaw = \core_enrol_external::get_enrolled_users($this->id, [['name' => 'onlyactive', 'value' => 1]]);
  //       static::$loadCount[] = $start - microtime(true);
  //       foreach ($usersRaw as $userRaw) {
  //       	$user = user::load($userRaw);
  //       	if ($user) {
  //       	 	$this->enrollUser($user);
  //       	}
  //       }
	}

	public function getCompletion($user)
	{
		$issues = [];
		foreach ($user->certificateIssues as $issue) {
			if (isset($issue->course) && $issue->course->id === $this->id) {
				$issues[] = (int) $issue->getMetaField('issue_date');
			}
		}
		if (!empty($issues)) {
			$maxIssueDate = max($issues);
			return date("Y-m-d", $maxIssueDate);
		}
		return null;
	}

	public function getCertificates()
	{
		return $this->_certificates;
	}

	public function addCertificate($cert)
	{
		$this->_certificates[$cert->id] = $cert;
	}

	public function getUsers()
	{
		return $this->_users;
	}

	public function enrollUser($user)
	{
		$user->enrollIn($this);
		$this->_users[$user->id] = $user;
	}
	
}
?>