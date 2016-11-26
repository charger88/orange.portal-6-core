<?php

namespace Orange\Portal\Core\App;

abstract class Installer
{

	protected $module;

	protected $params = [];

	protected $errors = [];

	public function __construct($module)
	{
		$this->module = $module;
	}

	protected function createThisModule()
	{
		$module_name = explode('_', get_class($this), 2)[1];
		$id = (new \Orange\Portal\Core\Model\Module())
			->setData([
				'module_code' => strtolower($module_name),
				'module_title' => 'MODULE_' . strtoupper($module_name),
				'module_status' => true,
			])
			->save()
			->id;
		return $id;
	}

	/**
	 * @param \Orange\Database\ActiveRecord[]
	 * @return bool
	 */
	protected function createTables($classes)
	{
		$result = true;
		$success = [];
		$errors = [];
		foreach ($classes as $classname) {
			try {
				$classname::install();
			} catch (\Orange\Database\DBException $e) {
				$result = false;
				$errors[] = $classname . ' --- ' . $e->getMessage() . ' --- ' . $e->getTraceAsString();
			}
			$success[] = $classname;
		}
		if (!$result) {
			$this->errors['db_prefix'] = implode("\n", $errors);
			if ($success) {
				foreach ($success as $classname) {
					(new \Orange\Database\Queries\Table\Drop($classname::getTableName()))
						->setIfExistsOnly()
						->execute();
				}
			}
		}
		return $result;
	}

	protected function createConfig($params)
	{
		$result = true;
		if ($params) {
			foreach ($params as $param => $type) {
				if ($type == 'array') {
					$this->params[$param] = json_encode($this->params[$param]);
				}
				$c = new \Orange\Portal\Core\Model\Config();
				$c->set('config_type', $type);
				$c->set('config_key', $this->module . '_' . $param);
				$c->set('config_value', isset($this->params[$param]) ? $this->params[$param] : null);
				$id = $c->save()->id;
				$result = $result && ($id > 0);
			}
		}
		return $result;
	}

	public function getErrors()
	{
		return $this->errors;
	}


}