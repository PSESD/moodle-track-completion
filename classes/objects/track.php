<?php
namespace report_trackcompletion\objects;

class track extends base {
	static $_tracks = array();
	protected $_loaded = false;
	public $courses = array();

	public static function load($parentCourse)
	{
		if (!empty($parentCourse) && isset(static::$_tracks[$parentCourse])) {
			return static::$_tracks[$parentCourse];
		}
		if (empty($parentCourse) || !($course = \get_course($parentCourse))) {
			return false;
		}
		return static::$_tracks[$parentCourse] = static::loadObject((array)$course);
	}

	public function postLoad()
	{
		if (!$this->_loaded) {
			if ($courses = $this->db->get_records('enrol', array('customint1' => $this->id, 'enrol' => 'meta'))) {
				foreach ($courses as $courseRaw) {
					$this->courses[] = (int) $courseRaw->courseid;
				}
			}
			$this->_loaded = true;
		}
		return parent::postLoad();
	}
}
?>