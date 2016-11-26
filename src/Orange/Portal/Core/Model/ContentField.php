<?php

namespace Orange\Portal\Core\Model;

use Orange\Database\Queries\Parts\Condition;

class ContentField extends \Orange\Database\ActiveRecord
{

	protected static $table = 'content_field';

	protected static $scheme = [
		'id' => ['type' => 'ID'],
		'content_id' => ['type' => 'INTEGER'],
		'content_field_name' => ['type' => 'STRING', 'length' => 32],
		'content_field_type' => ['type' => 'STRING', 'length' => 16],
		'content_field_value' => ['type' => 'DATA', 'length' => 2048],
	];

	protected static $keys = ['content_id'];
	protected static $u_keys = [['content_id', 'content_field_name']];


	public static function getObject($content_id, $field)
	{
		$select = (new \Orange\Database\Queries\Select(self::$table))
			->addWhere(new Condition('content_id', '=', $content_id))
			->addWhere(new Condition('content_field_name', '=', $field))
			->execute();
		$fieldObject = new ContentField($select->getResultNextRow());
		if (!$fieldObject->id) {
			$fieldObject->set('content_id', $content_id);
			$fieldObject->set('content_field_name', $field);
		}
		return $fieldObject;
	}

	public static function getContentIDs($name, $value)
	{
		return (new \Orange\Database\Queries\Select(self::$table))
			->addWhere(new Condition('content_field_name', '=', $name))
			->addWhere(new Condition('content_field_value', '=', $value))
			->addField('content_id')
			->execute()
			->getResultList('content_id');
	}

	public static function getRef($name)
	{
		return (new \Orange\Database\Queries\Select(self::$table))
			->addWhere(new Condition('content_field_name', '=', $name))
			->addField('content_id')
			->addField('content_field_value')
			->execute()
			->getResultColumn('content_id', 'content_field_value');
	}

}
