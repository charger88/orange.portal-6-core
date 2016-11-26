<?php

namespace Orange\Portal\Core\Model;

use Orange\Database\Queries\Parts\Condition;

/**
 * Class Block
 */
class Block extends Content
{

	/**
	 * @return int|null
	 */
	public function save()
	{
		if (!$this->get('content_type')) {
			$this->set('content_type', 'block');
		}
		if (!$this->id) {
			$this->set('content_time_published', time());
		}
		if (!$this->id) {
			$select = new \Orange\Database\Queries\Select(self::$table);
			$select
				->addField(['max', 'content_order'])
				->addWhere(new Condition('content_type', 'IN', ContentType::getBlockTypes()))
				->execute();
			$this->set('content_order', intval($select->getResultValue()) + 1);
		}
		return parent::save();
	}

	/**
	 * @param array|null $areas
	 * @param string|null $lang
	 * @param array|null $exclude_areas
	 * @param Content|null $content
	 * @param User|null $user
	 * @param bool $activeOnly
	 * @return array
	 */
	public static function getBlocksByAreas($areas = null, $lang = null, $exclude_areas = null, $content = null, $user = null, $activeOnly = false)
	{
		$by_areas = [];
		if (is_null($areas) || !empty($areas)) {
			$select = new \Orange\Database\Queries\Select(self::$table);
			$select->addWhere(new Condition('content_type', 'IN', ContentType::getBlockTypes()));
			$select->setOrder('content_order', \Orange\Database\Queries\Select::SORT_ASC);
			if (!empty($areas)) {
				$select->addWhere(new Condition('content_area', 'IN', $areas));
			}
			if (!empty($exclude_areas)) {
				$select->addWhere(new Condition('content_area', 'NOT IN', $exclude_areas));
			}
			if ($activeOnly) {
				$select->addWhere(new Condition('content_status', '>=', 5));
			}
			if (!is_null($lang)) {
				$select->addWhereOperator(Condition::L_AND);
				$select->addWhereBracket(true);
				$select->addWhere(new Condition('content_lang', 'LIKE', $lang));
				$select->addWhere(new Condition('content_lang', 'LIKE', ''), Condition::L_OR);
				$select->addWhereBracket(false);
				$select->addOrder('content_lang', true);
			}
			if (!is_null($content)) {
				$page_modes = [0];
				$page_modes[] = $content->get('content_parent_id') == 0 ? 1 : 2;
				$page_modes[] = $content->get('content_status') == 7 ? 3 : 4;
				$select->addWhere(new Condition('content_on_site_mode', 'IN', $page_modes));
			}
			if (!is_null($user)) {
				$groups = $user->get('user_groups');
				$groups[] = 0;
				$select->addWhereOperator(Condition::L_AND);
				$select->addWhereBracket(true);
				foreach ($groups as $n => $group_id) {
					$select->addWhere(new Condition('content_access_groups', 'LIKE', '%|' . $group_id . '|%'), Condition::L_OR);
				}
				$select->addWhereBracket(false);
			}
			$blocks = $select->execute()->getResultArray(null, __CLASS__);
			$added = [];
			foreach ($blocks as $block) {
				$dlID = $block->get('content_default_lang_id') ? $block->get('content_default_lang_id') : $block->id;
				if (!in_array($dlID, $added)) {
					if (!isset($by_areas[$block->get('content_area')])) {
						$by_areas[$block->get('content_area')] = [];
					}
					$by_areas[$block->get('content_area')][] = $block;
					$added[] = $dlID;
				}
			}
		}
		return $by_areas;
	}

}