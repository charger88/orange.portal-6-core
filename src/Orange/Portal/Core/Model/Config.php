<?php

namespace Orange\Portal\Core\Model;

use Orange\Database\Queries\Parts\Condition;

class Config extends \Orange\Database\ActiveRecord
{

	protected static $table = 'config';

	protected static $scheme = [
		'id' => ['type' => 'ID'],
		'config_status' => ['type' => 'BOOLEAN', 'default' => true],
		'config_type' => ['type' => 'STRING', 'length' => 16],
		'config_key' => ['type' => 'STRING', 'length' => 64],
		'config_value' => ['type' => 'DATA', 'length' => 512],
	];

	protected static $keys = ['config_status'];
	protected static $u_keys = ['config_key'];

	public static function loadActive($module = null)
	{
		$key = 'config_active_' . (is_null($module) ? 'all' : $module);
		if (!($ref = \Orange\Portal\Core\App\Portal::getInstance()->cache->get($key))) {
			$ref = [];
			$select = new \Orange\Database\Queries\Select(self::$table);
			$select->addWhere(new Condition('config_status', '=', 1));
			if (!is_null($module)) {
				$select->addWhere(new Condition('config_key', 'LIKE', $module . '_%'));
			}
			$select->execute();
			while ($row = $select->getResultNextRow()) {
				$ref[$row['config_key']] = unserialize($row['config_value']);
			}
			\Orange\Portal\Core\App\Portal::getInstance()->cache->set($key, $ref);
		}
		return $ref;
	}

	public static function loadList($module = null)
	{
		return (new \Orange\Database\Queries\Select(self::$table))
			->addWhere(new Condition('config_key', 'LIKE', $module . ':%'))
			->execute()
			->getResultArray(null, __CLASS__);
	}


}
