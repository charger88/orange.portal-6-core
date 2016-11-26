<?php

namespace Orange\Portal\Core\Model;

use Orange\Database\Queries\Parts\Condition;

/**
 * Class ContentTag
 */
class ContentTag extends \Orange\Database\ActiveRecord
{

	/**
	 * @var string
	 */
	protected static $table = 'content_tag';

	/**
	 * @var array
	 */
	protected static $scheme = [
		'id' => ['type' => 'ID'],
		'content_id' => ['type' => 'INTEGER'],
		'content_tag_value' => ['type' => 'STRING', 'length' => 64],
	];

	/**
	 * @var array
	 */
	protected static $keys = ['content_id'];

	public static function getTagsForContent($id)
	{
		return (new \Orange\Database\Queries\Select(static::$table))
			->addWhere(new Condition('content_id', '=', $id))
			->execute()
			->getResultList('content_tag_value');
	}

	public static function updateTagsForContent($id, $tags)
	{
		$tags = array_unique($tags);
		$old_tags = static::getTagsForContent($id);
		$old_tags = array_combine($old_tags, $old_tags);
		foreach ($tags as $i => $tag) {
			$tag = mb_strtolower(trim($tag));
			if (!in_array($tag, $old_tags)) {
				(new ContentTag())
					->set('content_id', $id)
					->set('content_tag_value', $tag)
					->save();
			} else {
				unset($old_tags[$tag]);
			}
		}
		if ($old_tags) {
			(new \Orange\Database\Queries\Delete(static::$table))
				->addWhere(new Condition('content_id', '=', $id))
				->addWhere(new Condition('content_tag_value', 'IN', $old_tags))
				->execute();
		}
		return (new \Orange\Database\Queries\Select(static::$table))
			->addWhere(new Condition('content_id', '=', $id))
			->execute()
			->getResultList('content_tag_value');
	}

	public static function getCloudData($limit)
	{
		$tags = self::getPopularTags($limit);
		ksort($tags);
		return $tags;
	}

	public static function getPopularTags($limit)
	{
		return (new \Orange\Database\Queries\Select(static::$table))
			->addField('content_tag_value')
			->addField(['count', '*'], 'cnt')
			->setGroupBy('content_tag_value')
			->setOrder('cnt', \Orange\Database\Queries\Select::SORT_DESC)
			->setLimit($limit)
			->execute()
			->getResultColumn('content_tag_value', 'cnt');
	}

	public static function tagsStats($tags)
	{
		$min = $max = null;
		foreach ($tags as $cnt) {
			if (is_null($min)) {
				$min = $max = $cnt;
			} else {
				if ($cnt > $max) {
					$max = $cnt;
				}
				if ($cnt < $min) {
					$min = $cnt;
				}
			}
		}
		$avg = $tags ? array_sum($tags) / count($tags) : 0;
		return [$min, $max, $avg];
	}

	public static function deleteTagsByContent($id)
	{
		(new \Orange\Database\Queries\Delete(static::$table))
			->addWhere(new Condition('content_id', '=', $id))
			->execute();
	}

}