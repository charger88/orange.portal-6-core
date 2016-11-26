<?php

namespace Orange\Portal\Core\Model;

use Orange\Database\Queries\Parts\Condition;

/**
 * Class Privilege
 */
class Privilege extends \Orange\Database\ActiveRecord
{

	/**
	 * @var string
	 */
	protected static $table = 'privilege';

	/**
	 * @var array
	 */
	protected static $scheme = [
		'id' => ['type' => 'ID'],
		'privilege_name' => ['type' => 'STRING', 'length' => 64],
		'user_group_id' => ['type' => 'INTEGER'],
	];

	/**
	 * @var array
	 */
	protected static $u_keys = [['privilege_name', 'user_group_id']];

	/**
	 * @param string $name
	 * @param User $user
	 * @return bool
	 */
	public static function hasPrivilege($name, $user)
	{
		if (is_null(static::$privileges_by_group)){
			static::getPrivilegesByGroup();
		}
		$user_groups = $user->get('user_groups');
		$user_groups[] = 0;
		if (!in_array(User::GROUP_ADMIN, $user_groups)) {
			foreach (static::$privileges_by_group as $group_id => $privilegies) {
				if (in_array($name, $privilegies)){
					return true;
				}
			}
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @param array $data
	 */
	public static function massPrivilegesDeleting($data)
	{
		if ($data) {
			$delete = new \Orange\Database\Queries\Delete(self::$table);
			$first = true;
			foreach ($data as $group_id => $privileges) {
				if ($privileges) {
					if ($first) {
						$first = false;
					} else {
						$delete->addWhereOperator(Condition::L_OR);
					}
					$delete->addWhereBracket(true);
					$delete->addWhere(new Condition('user_group_id', '=', $group_id));
					$delete->addWhere(new Condition('privilege_name', 'IN', $privileges));
					$delete->addWhereBracket(false);
				}
			}
			$delete->execute();
		}
	}

	/**
	 * @param array $data
	 */
	public static function massPrivilegesAdding($data)
	{
		if ($data) {
			foreach ($data as $group_id => $privileges) {
				if ($privileges) {
					foreach ($privileges as $privilege) {
						$item = new Privilege();
						$item->set('privilege_name', $privilege);
						$item->set('user_group_id', $group_id);
						$item->save();
					}
				}
			}
		}
	}

	/**
	 * @var array
	 */
	private static $privileges_by_group;

	/**
	 * @return array
	 */
	public static function getPrivilegesByGroup()
	{
		$key = 'privilegies';
		if (is_null(static::$privileges_by_group)){
			if (!(static::$privileges_by_group = \Orange\Portal\Core\App\Portal::getInstance()->cache->get($key))) {
				static::$privileges_by_group = [];
				$result = (new \Orange\Database\Queries\Select(self::$table))
					->execute()
					->getResultArray();
				if ($result) {
					foreach ($result as $row) {
						if (!isset(static::$privileges_by_group[$row['user_group_id']])) {
							static::$privileges_by_group[$row['user_group_id']] = [];
						}
						static::$privileges_by_group[$row['user_group_id']][] = $row['privilege_name'];
					}
				}
				\Orange\Portal\Core\App\Portal::getInstance()->cache->set($key, static::$privileges_by_group);
			}
		}
		return static::$privileges_by_group;
	}

}