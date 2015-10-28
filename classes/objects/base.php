<?php
namespace report_trackcompletion\objects;

class base extends \report_trackcompletion\object {
	protected static $_registry = [];
	protected $_id;
	public $meta;

	public function init()
	{
		parent::init();
	}

	public function postLoad()
	{

	}

	public function getMetaField($id)
	{
		if (isset($this->meta[$id])) {
			return $this->meta[$id];
		}
		return null;
	}

	public static function loadObject($meta)
	{
		$id = static::generateId($meta);
		if (!($object = static::getById($id))) {
			$object = new static(['id' => $id, 'meta' => $meta]);
			if (!isset(static::$_registry[get_called_class()])) {
				static::$_registry[get_called_class()] = [];
			}
			static::$_registry[get_called_class()][$id] = $object;
			if ($object->isLoaded()) {
				$object->postLoad();
			}
		} elseif (!$object->isLoaded()) {
			$object->meta = $meta;
			$object->postLoad();
		}

		return $object;
	}

	public static function getById($id, $allowLazy = false) {
		if (!isset(static::$_registry[get_called_class()])) {
			static::$_registry[get_called_class()] = [];
		}
		if (isset(static::$_registry[get_called_class()][$id])) {
			return static::$_registry[get_called_class()][$id];
		} elseif ($allowLazy) {
			return static::loadObject(['id' => $id]);
		}
		return false;
	}

	public function isLoaded()
	{
		return count($this->meta) > 1;
	}

	public static function getRegistrySize()
	{
		if (!isset(static::$_registry[get_called_class()])) {
			static::$_registry[get_called_class()] = [];
		}
		return count(static::$_registry[get_called_class()]);
	}

	public function setId($id)
	{
		$this->_id = $id;
	}

	public function getId()
	{
		if (!isset($this->_id)) {
			$this->_id = static::generateId($this->meta);
		}
		return $this->_id;
	}

	public function getIsValid()
	{
		return true;
	}

	public static function getAll($onlyValid = true)
	{
		if (!isset(static::$_registry[get_called_class()])) {
			static::$_registry[get_called_class()] = [];
		}
		if ($onlyValid) {
			$valid = [];
			foreach (static::$_registry[get_called_class()] as $key => $test) {
				if ($test->isValid) {
					$valid[$key] = $test;
				}
			}
			return $valid;
		}
		return static::$_registry[get_called_class()];
	}

	public static function generateId($meta)
	{
		if (!isset($meta['id'])) {
			throw \Exception("ID for ". get_called_class() ." not found");
		}
		return $meta['id'];
	}
}
?>