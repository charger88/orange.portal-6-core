<?php

namespace Orange\Portal\Core\Model;

use Orange\Database\Queries\Parts\Condition;

/**
 * Class Page
 */
class Page extends Content
{

	/**
	 * @return int|null
	 */
	public function save()
	{
		if (!$this->get('content_type')) {
			$this->set('content_type', 'page');
		}
		if (!$this->id) {
			$select = new \Orange\Database\Queries\Select(self::$table);
			$select
				->addField(['max', 'content_order'])
				->addWhere(new Condition('content_type', 'IN', ContentType::getPageTypes()))
				->execute();
			$this->set('content_order', intval($select->getResultValue()) + 1);
		}
		return parent::save();
	}

	/**
	 * @return array
	 */
	public function getParentsRef()
	{
		return [0 => ''] + self::getList(
			[
				'types' => ['page'],
				'exclude' => [$this->id]
			],
			[
				'id' => 'content_title',
			]
		);
	}

	/**
	 * @param $lang
	 * @return Content
	 */
	public static function getHomepage($lang)
	{
		$select = new \Orange\Database\Queries\Select(self::$table);
		$select
			->addWhere(new Condition('content_status', '=', 7))
			->addWhere(new Condition('content_lang', 'IN', [$lang, '']))
			->addWhere(new Condition('content_type', 'IN', ContentType::getPageTypes()))
			->setOrder('content_lang', \Orange\Database\Queries\Select::SORT_DESC)
			->execute();
		return new Page($select->getResultNextRow());
	}

	/**
	 * @param User $user
	 * @param boolean $ignoreOnSiteMode
	 * @param null $status_min
	 * @return array
	 */
	public static function getPagesByParents($user, $ignoreOnSiteMode = false, $status_min = null)
	{
		$grouped = [];
		$params = [
			'access_user' => $user,
			'order' => 'content_order',
			'types' => ContentType::getPageTypes(),
		];
		if (!is_null($status_min)){
			$params['status_min'] = $status_min;
		}
		if (!$ignoreOnSiteMode) {
			$params['on_site_mode'] = [2, 3];
		}
		$pages = self::getList($params, __CLASS__);
		if ($pages) {
			foreach ($pages as $item) {
				if (!isset($grouped[$item->get('content_parent_id')])) {
					$grouped[$item->get('content_parent_id')] = [];
				}
				$grouped[$item->get('content_parent_id')][] = $item;
			}
		}
		return $grouped;
	}

	/**
	 * @param User $user
	 * @param int $root
	 * @return Page[]
	 */
	public static function getMenu($user, $root = 0)
	{
		$params = [
			'types' => ContentType::getPageTypes(),
			'access_user' => $user,
			'parent_id' => $root,
			'on_site_mode' => [1, 3],
			'order' => 'content_order',
		];
		return self::getList($params, __CLASS__);
	}

	//TODO Think about optimization
	/**
	 * @param User $user
	 * @param string $lang
	 * @param int $root
	 * @param int $tree_levels
	 * @return array
	 */
	public static function getTreeMenu($user, $lang, $root = 0, $tree_levels = 0)
	{
		$params = [
			'types' => ContentType::getPageTypes(),
			'access_user' => $user,
			'lang' => [$lang, ''],
			'on_site_mode' => [2, 3],
			'order' => 'content_order',
		];
		$menu = [];
		$pages = self::getList($params, __CLASS__);
		foreach ($pages as $page) {
			if (!isset($menu[$page->get('content_parent_id')])) {
				$menu[$page->get('content_parent_id')] = [];
			}
			$menu[$page->get('content_parent_id')][$page->get('content_default_lang_id') ? $page->get('content_default_lang_id') : $page->id] = $page;
		}
		return $menu;
	}

}