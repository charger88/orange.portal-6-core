<?php

namespace Orange\Portal\Core\Model;

use Orange\Database\Queries\Parts\Condition;

/**
 * Class Module
 */
class Module extends \Orange\Database\ActiveRecord
{

	/**
	 * @var string
	 */
	protected static $table = 'module';

	/**
	 * @var array
	 */
	protected static $scheme = [
		'id' => ['type' => 'ID'],
		'module_code' => ['type' => 'STRING', 'length' => 32],
		'module_title' => ['type' => 'STRING', 'length' => 128],
		'module_status' => ['type' => 'BOOLEAN'],
	];

	/**
	 * @var array
	 */
	protected static $keys = ['module_status'];
	/**
	 * @var array
	 */
	protected static $u_keys = ['module_code'];

	/**
	 * @param bool $active_only
	 * @return \Orange\Portal\Core\App\Module[]
	 */
	public static function getModules($active_only = false)
	{
		$key = 'modules_' . ($active_only ? 'active' : 'all');
		if (!($modules = \Orange\Portal\Core\App\Portal::getInstance()->cache->get($key))) {
			$modules = [];
			$select = new \Orange\Database\Queries\Select(self::$table);
			if ($active_only) {
				$select->addWhere(new Condition('module_status', '=', 1));
			}
			$select->setOrder('id');
			if ($result = $select->execute()->getResultArray(null, __CLASS__)) {
				foreach ($result as $module) {
					/** @var Module $module */
					if ($moduleObject = $module->getModuleObject()) {
						$modules[] = $moduleObject;
					}
				}
			}
			\Orange\Portal\Core\App\Portal::getInstance()->cache->set($key, $modules);
		}
		return $modules;
	}

	/**
	 * @return \Orange\Portal\Core\App\Module[]
	 */
	public static function getNotInstalledModules()
	{
		$modules = [];
		$modulesDir = new \Orange\FS\Dir('modules');
		$dirs = $modulesDir->readDir();
		foreach ($dirs as $dir) {
			if ($dir instanceof $dir) {
				$module = new Module('module_code', $dir->getName());
				if (!$module->id) {
					$module->set('module_code', $dir->getName());
					$modules[] = $module->getModuleObject();
				}
			}
		}
		return $modules;
	}

	/**
	 * @return \Orange\Portal\Core\App\Module|null
	 */
	public function getModuleObject()
	{
		if ($classname = $this->get('module_code')) {
			$classname = 'OPMO_' . ucfirst($classname);
			return new $classname($this->getData());
		} else {
			return null;
		}
	}


}