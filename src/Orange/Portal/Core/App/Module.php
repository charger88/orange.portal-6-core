<?php

namespace Orange\Portal\Core\App;

abstract class Module extends \Orange\Portal\Core\Model\Module
{

	public function __construct($data = [])
	{
		if (!is_null($data)) {
			$code = strtolower(substr(get_class($this), 5));
			if (is_array($data) && !empty(is_array($data)) && ($data['module_code'] == $code)) {
				parent::__construct($data);
			} else {
				parent::__construct('module_code', $code);
			}
		} else {
			parent::__construct();
		}
	}

	public function init()
	{
		if ($this->id && $this->get('module_status')) {
			return $this->initHooks()->doInit();
		}
		return null;
	}

	public function getInstallForm()
	{
		return null;
	}

	public function installModule($params = [])
	{
		if (!$this->id && !$this->get('module_status')) {
			Lang::load('modules/' . $this->get('module_code') . '/lang/admin', Portal::$sitelang);
			return $this->doInstall($params);
		}
		return null;
	}

	public function enable()
	{
		if ($this->id && !$this->get('module_status')) {
			$this->set('module_status', 1);
			$this->save();
			return $this->doEnable();
		}
		return null;
	}

	public function disable()
	{
		if ($this->id && $this->get('module_status')) {
			$this->set('module_status', 0);
			$this->save();
			return $this->doDisable();
		}
		return null;
	}

	public function uninstallModule()
	{
		if ($this->id && !$this->get('module_status')) {
			$this->delete();
			return $this->doUninstall();
		}
		return null;
	}

	protected function initHooks()
	{
		try {
			$hooks_file = new \Orange\FS\File('modules/' . $this->get('module_code') . '/hooks.php');
			if ($hooks_file->exists()) {
				$hooks = include $hooks_file->getPath();
				if ($hooks && is_array($hooks)) {
					foreach ($hooks as $hook => $hook_functions) {
						foreach ($hook_functions as $hook_function) {
							Portal::getInstance()->addHook($hook, $hook_function);
						}
					}
				}
			}
		} catch (Exception $e) {
		}
		return $this;
	}

	protected abstract function doInit();

	protected abstract function doInstall($params);

	protected abstract function doEnable();

	protected function doDisable()
	{
		$this->set('module_status', 0);
		$this->save();
		return null;
	}

	protected abstract function doUninstall();

	public function getAdminMenu()
	{
		$menu = [];
		try {
			$menu_file = new \Orange\FS\File('modules/' . $this->get('module_code') . '/admin-menu.json');
			if ($menu_file->exists()) {
				$menu = json_decode($menu_file->getData(), true);
				if (!$menu) {
					$menu = [];
				}
			}
		} catch (Exception $e) {
		}
		return $menu;
	}

	protected $privileges = [];

	public function getInfo()
	{
		$info = [
			'title' => '',
			'description' => '',
			'code' => '',
			'version' => '',
			'author' => '',
			'author_url' => '',
		];
		if ($this->get('module_code')) {
			$file = new \Orange\FS\File('modules/' . $this->get('module_code'), 'info.json');
			if ($file->exists()) {
				$file = json_decode($file->getData(), true);
				foreach ($info as $key => $value) {
					if (isset($file[$key])) {
						$info[$key] = $file[$key];
					}
				}
			}
		}
		return $info;
	}

	public function getPrivilegesList()
	{
		return array_unique(array_values($this->privileges));
	}

	public function getPrivilege($controllername, $methodname)
	{
		$privilegename = $controllername . '::' . $methodname;
		return isset($this->privileges[$privilegename]) ? $this->privileges[$privilegename] : null;
	}

}