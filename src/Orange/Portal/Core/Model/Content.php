<?php

namespace Orange\Portal\Core\Model;

use Orange\Database\Queries\Parts\Condition;

/**
 * Class \Orange\Portal\Core\Model\Content
 */
class Content extends \Orange\Database\ActiveRecord
{

	/**
	 * @var string
	 */
	protected static $table = 'content';

	/**
	 * @var array
	 */
	protected static $scheme = [
		'id' => ['type' => 'ID'],
		'content_type' => ['type' => 'STRING', 'length' => 32],
		'content_title' => ['type' => 'STRING', 'length' => 1024],
		'content_parent_id' => ['type' => 'INTEGER'],
		'content_order' => ['type' => 'INTEGER'],
		'content_access_groups' => ['type' => 'LIST', 'length' => 256],
		'content_lang' => ['type' => 'STRING', 'length' => 2],
		'content_area' => ['type' => 'STRING', 'length' => 32],
		'content_slug' => ['type' => 'STRING', 'length' => 1024],
		'content_default_lang_id' => ['type' => 'INTEGER'],
		'content_on_site_mode' => ['type' => 'TINYINT'],
		'content_status' => ['type' => 'TINYINT'],
		'content_commands' => ['type' => 'ARRAY', 'length' => 8192],
		'content_template' => ['type' => 'STRING', 'length' => 32],
		'content_image' => ['type' => 'INTEGER'],
		'content_time_modified' => ['type' => 'TIME'],
		'content_time_published' => ['type' => 'TIME'],
		'content_user_id' => ['type' => 'INTEGER'],
	];

	const STATUS_REMOVED = 0;
	const STATUS_CANCELED = 1;
	const STATUS_DISABLED = 2;
	const STATUS_DRAFT = 3;
	const STATUS_MODERATION = 4;
	const STATUS_ENABLED = 5;
	const STATUS_APPROVED = 6;
	const STATUS_HOMEPAGE = 7;

	/**
	 * @var array
	 */
	private $fields = [];

	/**
	 * @var array
	 */
	private $tags = null;

	/**
	 * @var array
	 */
	protected static $keys = ['content_type', 'content_parent_id', 'content_order', 'content_lang', 'content_area', 'content_slug', 'content_on_site_mode', 'content_status', 'content_time_published', 'content_user_id'];

	/**
	 * @param null $key
	 * @param null $value
	 * @throws \Orange\Database\DBException
	 * @throws \Exception
	 */
	public function __construct($key = null, $value = null)
	{
		parent::__construct($key, $value);
		if ($this->id) {
			if (!in_array($this->get('content_type'), static::getTypesAllowedForThisClass())) {
				throw new \Exception('Wrong combination of content (#' . $this->id . ') type and class: ' . $this->get('content_type') . ' / ' . get_class($this));
			}
		} else {
			$classname = strtolower(get_class($this));
			if (strpos($classname, '\\') !== false) {
				$classname = explode('\\', $classname);
				$this->set('content_type', array_pop($classname));
			} else {
				$this->set('content_type', explode('_', $classname, 2)[1]);
			}
		}
	}

	/**
	 * @return int|null
	 */
	public function save()
	{
		if (!$this->get('content_time_published')) {
			$this->set('content_time_published', time());
		}
		$this->set('content_time_modified', time());
		$id = parent::save()->id;
		$type = new ContentType('content_type_code', $this->get('content_type'));
		$field_IDs = [];
		if ($fields = $type->get('content_type_fields')) {
			$this->loadFields();
			foreach ($fields as $field_id => $field) {
				$fieldObject = ContentField::getObject($id, $field_id);
				$value = isset($this->fields[$field_id]) ? $this->fields[$field_id] : '';
				if (($fieldObject->get('content_field_type') != $field['type']) || ($fieldObject->get('content_field_value') != $value)) {
					$fieldObject->set('content_field_type', $field['type']);
					$fieldObject->set('content_field_value', $value);
					$field_IDs[] = $fieldObject->save()->id;
				} else {
					$field_IDs[] = $fieldObject->id;
				}
			}
		}
		$delete = new \Orange\Database\Queries\Delete('content_field');
		$delete->addWhere(new Condition('content_id', '=', $id));
		if ($field_IDs) {
			$delete->addWhere(new Condition('id', 'NOT IN', $field_IDs));
		}
		$delete->execute();
		return $this;
	}

	/**
	 * @param bool $null
	 * @return bool|null
	 */
	public function delete($null = false)
	{
		if ($this->id) {
			(new \Orange\Database\Queries\Delete('content_field'))
				->addWhere(new Condition('content_id', '=', $this->id))
				->execute();
			(new \Orange\Database\Queries\Delete('content_text'))
				->addWhere(new Condition('content_id', '=', $this->id))
				->execute();
			ContentTag::deleteTagsByContent($this->id);
		}
		return parent::delete();
	}


	/**
	 * @param string $role
	 * @return \Orange\Portal\Core\Model\ContentText
	 */
	public function text($role = 'text')
	{
		if ($this->id) {
			$select = (new \Orange\Database\Queries\Select('content_text'))
				->addWhere(new Condition('content_id', '=', $this->id))
				->addWhere(new Condition('content_text_role', '=', $role))
				->execute();
			$text = new ContentText($select->getResultNextRow());
			if (!$text->id) {
				$text->set('content_id', $this->id);
				$text->set('content_text_role', $role);
			}
			return $text;
		} else {
			return new ContentText();
		}
	}

	/**
	 * @param string $field
	 * @param mixed $value
	 */
	public function setField($field, $value)
	{
		$this->fields[$field] = $value;
	}

	/**
	 * Load content fields from DB
	 */
	protected function loadFields()
	{
		$select = (new \Orange\Database\Queries\Select('content_field'))
			->addField('content_field_name')
			->addField('content_field_value')
			->addWhere(new Condition('content_id', '=', $this->id))
			->execute();
		$fields = $select->getResultColumn('content_field_name', 'content_field_value');
		foreach ($fields as $key => $value) {
			$fields[$key] = unserialize($value);
		}
		$this->fields = array_merge($fields, $this->fields);
	}

	/**
	 * @param string $field
	 * @return mixed
	 */
	public function field($field = '')
	{
		if (!array_key_exists($field, $this->fields) && $this->id) {
			$this->loadFields();
		}
		if (!array_key_exists($field, $this->fields)) {
			$this->fields[$field] = null;
		}
		return $this->fields[$field];
	}

	/**
	 * @return \Orange\Portal\Core\Model\Content
	 */
	public function tags()
	{
		if (is_null($this->tags)) {
			$this->tags = ContentTag::getTagsForContent($this->id);
		}
		return $this->tags;
	}

	/**
	 * @var string[] $tags
	 * @return \Orange\Portal\Core\Model\Content
	 */
	public function updateTags($tags)
	{
		$this->tags = ContentTag::updateTagsForContent($this->id, $tags);
		return $this;
	}

	/**
	 * @param string|null $default_lang
	 * @return string
	 */
	public function getSlug($default_lang = null)
	{
		$slug = '';
		if (!is_null($default_lang) && $this->get('content_lang') && ($this->get('content_lang') != $default_lang)) {
			$slug = $this->get('content_lang');
		}
		if ($this->get('content_status') < 7) {
			$slug .= '/' . str_replace('%2F', '/', $this->get('content_slug'));
		}
		return trim($slug, '/');
	}

	/**
	 * @param string $default_lang
	 * @param string $current_lang
	 * @param string $for_lang
	 * @param string $get_string
	 * @return string
	 */
	public function getURL($default_lang = null, $current_lang = null, $for_lang = null, $get_string = '')
	{
		$url = OP_WWW . '/' . $this->getSlug($default_lang);
		if (is_null($for_lang)) {
			$for_lang = $current_lang;
		}
		$get_string = trim($get_string, '&');
		$get_added = false;
		if (!is_null($default_lang) && !is_null($current_lang) && !is_null($for_lang)) {
			if (empty($this->get('content_lang')) && ($default_lang !== $for_lang)) {
				$url .= '?' . ($get_string ? $get_string . '&' : '') . 'lang=' . $for_lang;
				$get_added = true;
			}
		}
		if (!$get_added && $get_string) {
			$url .= '?' . $get_string;
		}
		return $url;
	}

	const SLUG_UNIQUE_MODE_NONE = 0;
	const SLUG_UNIQUE_MODE_ID = 1;
	const SLUG_UNIQUE_MODE_DATE = 2;

	/**
	 * @param int $unique_mode
	 * @return \Orange\Portal\Core\Model\Content
	 */
	function generateSlug($unique_mode = self::SLUG_UNIQUE_MODE_NONE)
	{
		$slug = mb_strtolower($this->get('content_title'));
		if (strlen($slug) > 96) {
			if (($sp = strpos($slug, ' ', 96)) !== false) {
				$slug = substr($slug, 0, $sp);
			} else {
				if (($sp = strpos($slug, ' ', 64)) !== false) {
					$slug = substr($slug, 0, $sp);
				} else {
					$slug = md5($slug);
				}
			}
		}
		$slug = preg_replace('/[^\p{L}0-9]/u', '-', $slug);
		$slug = trim($slug, '-');
		while (strpos($slug, '--') !== false) {
			$slug = str_replace('--', '-', $slug);
		}
		if ($unique_mode == self::SLUG_UNIQUE_MODE_ID) {
			$slug .= '.' . $this->id;
		} else if ($unique_mode == self::SLUG_UNIQUE_MODE_DATE) {
			$slug .= '.' . date("Ymd-Hi");
		}
		$slug .= '.html';
		$this->set('content_slug', urlencode($slug));
		return $this;
	}

	/**
	 * @param null $type
	 * @param null $lang
	 * @param null $slug
	 * @return mixed
	 */
	public static function getContent($type = null, $lang = null, $slug = null)
	{
		$select = new \Orange\Database\Queries\Select(static::$table);
		$select->addWhere(new Condition('id', '>', 0));
		if (!is_null($type)) {
			$select->addWhere(new Condition('content_type', 'LIKE', $type));
		}
		if (!is_null($lang)) {
			$select->addWhereOperator(Condition::L_AND);
			$select->addWhereBracket(true);
			$select->addWhere(new Condition('content_lang', 'LIKE', $lang));
			$select->addWhere(new Condition('content_lang', 'LIKE', ''), Condition::L_OR);
			$select->addWhereBracket(false);
			$select->setOrder('content_lang', \Orange\Database\Queries\Select::SORT_DESC);
		}
		if (!is_null($slug)) {
			$select->addWhere(new Condition('content_slug', 'LIKE', $slug));
		}
		$select->setLimit(1);
		$row = $select->execute()->getResultNextRow();
		if (is_null($type)) {
			$type = $row['content_type'];
		}
		$typeObject = new ContentType('content_type_code', $type);
		$classname = $typeObject->getClass();
		return new $classname($row);
	}

	protected static function getTypesAllowedForThisClass($requested_types = null)
	{
		if (!is_null($requested_types)) {
			if (!is_array($requested_types)) {
				$requested_types = [$requested_types];
			}
		}
		$class = get_called_class();
		if (strpos($class, '\\') !== false) {
			$class = explode('\\', strtolower(get_called_class()));
			$class = array_pop($class);
		} else {
			$class = strtolower(explode('_', $class, 2)[1]);
		}
		if ($class == 'content') {
			$allowed_types = is_null($requested_types) ? ContentType::getTypes(null, null, 'codes') : $requested_types;
		} else if (in_array($class, ($page_types = ContentType::getPageTypes()))) {
			$allowed_types = is_null($requested_types) ? $page_types : array_intersect($requested_types, $page_types);
		} else if (in_array($class, ($block_types = ContentType::getBlockTypes()))) {
			$allowed_types = is_null($requested_types) ? $block_types : array_intersect($requested_types, $block_types);
		} else {
			$allowed_types = [$class];
		}
		return $allowed_types;
	}

	/**
	 * @param array $params
	 * @param string|null $classname
	 * @param \Orange\Database\Queries\Select|null $select_base
	 * @return \Orange\Portal\Core\Model\Content[]|array
	 */
	public static function getList($params = [], $classname = null, $select_base = null)
	{
		$IDs = isset($params['IDs']) ? $params['IDs'] : null;
		$exclude = isset($params['exclude']) ? $params['exclude'] : null;
		$types = static::getTypesAllowedForThisClass(isset($params['types']) ? $params['types'] : null);
		if (empty($types)) {
			return [];
		}
		$search = isset($params['search']) ? $params['search'] : null;
		$searchmode = isset($params['searchmode']) ? $params['searchmode'] : 0;
		$tag = isset($params['tag']) ? $params['tag'] : null;
		$fields = isset($params['fields']) ? $params['fields'] : null;
		$fields_not = !empty($params['fields_not']);
		$status_min = isset($params['status_min']) ? $params['status_min'] : self::STATUS_ENABLED;
		$status_max = isset($params['status_max']) ? $params['status_max'] : null;
		$access_user = isset($params['access_user']) ? $params['access_user'] : null;
		$lang = isset($params['lang']) ? $params['lang'] : null;
		$on_site_mode = isset($params['on_site_mode']) ? $params['on_site_mode'] : null;
		$time_published_from = isset($params['time_published_from']) ? $params['time_published_from'] : null;
		$time_published_to = isset($params['time_published_to']) ? $params['time_published_to'] : null;
		$user_id = isset($params['user_id']) ? $params['user_id'] : null;
		$parent_id = isset($params['parent_id']) ? $params['parent_id'] : null;

		$limit = isset($params['limit']) ? $params['limit'] : null;
		$offset = isset($params['offset']) ? $params['offset'] : 0;
		$order = isset($params['order']) ? $params['order'] : 'content_time_published';
		$desc = isset($params['desc']) && $params['desc'] ? \Orange\Database\Queries\Select::SORT_DESC : \Orange\Database\Queries\Select::SORT_ASC;

		$select = is_null($select_base) ? new \Orange\Database\Queries\Select(static::$table) : $select_base;

		if (!is_null($IDs) && $IDs) {
			$select->addWhere(new Condition('id', 'IN', $IDs), Condition::L_AND);
		} else {
			$select->addWhere(new Condition('id', '>', 0), Condition::L_AND);
		}

		if (!is_null($exclude) && $exclude) {
			$select->addWhere(new Condition('id', 'NOT IN', $exclude));
		}

		if (!is_null($types)) {
			$select->addWhere($types ? new Condition('content_type', 'IN', is_array($types) ? $types : [$types]) : new Condition('id', '=', 0));
		}

		if ($fields) {
			$fieldsSelect = new \Orange\Database\Queries\Select('content_field');
			$fieldsSelect->addField('content_id');
			foreach ($fields as $param => $value) {
				$fieldsSelect->addWhere(new Condition('content_field_name', '=', $param), Condition::L_OR);
				$fieldsSelect->addWhere(new Condition('content_field_value', strpos($value, '%') !== false ? 'LIKE' : '=', $value));
			}
			if ($fids = $fieldsSelect->execute()->getResultList('content_id')) {
				$select->addWhere(new Condition('id', $fields_not ? 'NOT IN' : 'IN', $fids));
			} else {
				if (!$fields_not) {
					return [];
				}
			}
		}

		if (!is_null($search)) {
			$select->addWhere(new Condition('content_template', 'LIKE', 'main-%'));
			$select->addWhereOperator(Condition::L_AND);
			$select->addWhereBracket(true);
			if (($searchmode == 0) || ($searchmode == 1)) {
				$select->addWhere(new Condition('content_title', 'LIKE', '%' . $search . '%'));
			}
			if ($searchmode == 0) {
				$select->addWhereOperator(Condition::L_OR);
			}
			if (($searchmode == 0) || ($searchmode == 2)) {
				$textsSelect = new \Orange\Database\Queries\Select('content_text');
				$textsSelect->addWhere(new Condition('content_text_value', 'LIKE', '%' . $search . '%'));
				if (!is_null($exclude) && $exclude) {
					$textsSelect->addWhere(new Condition('content_id', 'NOT IN', $exclude));
				}
				$textsSelect->addField('content_id');
				$select->addWhere(new Condition('id', 'IN', $textsSelect));
			}
			$select->addWhereBracket(false);
		}

		if (!is_null($tag)) {
			$select->addWhere(new Condition('content_template', 'LIKE', 'main-%'));
			$select->addWhere(new Condition('id', 'IN', (new \Orange\Database\Queries\Select('content_tag'))->addWhere(new Condition('content_tag_value', 'LIKE', $tag))->addField('content_id')));
		}

		if (!is_null($access_user) && ($access_user instanceof User)) {
			$groups = $access_user->get('user_groups');
			$groups[] = 0;
			$select->addWhereOperator(Condition::L_AND);
			$select->addWhereBracket(true);
			foreach ($groups as $n => $group_id) {
				$select->addWhere(new Condition('content_access_groups', 'LIKE', '%|' . $group_id . '|%'), Condition::L_OR);
			}
			$select->addWhereBracket(false);
		}

		if (!is_null($lang)) {
			$select->addWhere(new Condition('content_lang', is_array($lang) ? 'IN' : 'LIKE', $lang));
		}

		if (!is_null($on_site_mode)) {
			$select->addWhere($on_site_mode ? new Condition('content_on_site_mode', 'IN', $on_site_mode) : new Condition('id', '=', 0));
		}

		if (!is_null($status_min)) {
			$select->addWhere(new Condition('content_status', '>=', $status_min));
		}

		if (!is_null($status_max)) {
			$select->addWhere(new Condition('content_status', '<=', $status_max));
		}

		if (!is_null($time_published_from)) {
			$select->addWhere(new Condition('content_time_published', '>=', date("Y-m-d H:i:s", strtotime($time_published_from))));
		}

		if (!is_null($time_published_to)) {
			$select->addWhere(new Condition('content_time_published', '<', date("Y-m-d H:i:s", strtotime($time_published_to))));
		}

		if (!is_null($user_id)) {
			$select->addWhere(new Condition('content_user_id', 'IN', is_array($user_id) ? $user_id : [$user_id]));
		}

		if (!is_null($parent_id)) {
			$select->addWhere(new Condition('content_parent_id', 'IN', is_array($parent_id) ? $parent_id : [$parent_id]));
		}

		if (!is_null($order)) {
			$select->setOrder($order, $desc);
		}

		if (!is_null($limit)) {
			$select->setLimit($limit);
			$select->setOffset($offset * $limit);
		}

		$is_column = false;

		if (is_array($classname)) {
			foreach ($classname as $key => $field) {
				if (!is_numeric($key)) {
					$select->addField($key);
					$is_column = true;
				}
				$select->addField($field);
			}
			if (!$is_column) {
				if (!in_array('id', $classname)) {
					$select->addField('id');
				}
			}
		}

		$select->execute();

		return is_array($classname)
			? ($is_column
				? $select->getResultColumn(key($classname), current($classname))
				: $select->getResultArray('id')
			)
			: $select->getResultArray('id', is_null($classname) ? __CLASS__ : $classname);
	}

	/**
	 * @param array $user_groups
	 * @return bool
	 */
	public function isAllowedForGroups($user_groups)
	{
		if (!in_array(User::GROUP_ADMIN, $user_groups)) {
			$access_groups = $this->get('content_access_groups');
			if (in_array(0, $access_groups)) {
				return true;
			} else {
				if ($user_groups) {
					foreach ($user_groups as $group_id) {
						if (in_array($group_id, $access_groups)) {
							return true;
						}
					}
				}
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * @param string $lang
	 * @return array
	 */
	public function getDefaultLanguageRef($lang)
	{
		$tmp = static::getList([
			'types' => $this->get('content_type'),
			'lang' => $lang,
			'status_min' => Content::STATUS_DRAFT,
		], [
			'id',
			'content_title'
		]);
		$defaultLanguageRef = [];
		foreach ($tmp as $t) {
			$defaultLanguageRef[$t['id']] = $t['content_title'];
		}
		return $defaultLanguageRef;
	}

	/**
	 * @param string|null $th
	 * @return string
	 */
	public function getImageUrl($th = null)
	{
		$url = '';
		if ($iid = $this->get('content_image')) {
			$media = new \OPMM_System_Media($iid);
			$url = $media->getDir($th) . '/' . $media->get('media_file');
		}
		return $url;
	}

	/**
	 * @param array $user_groups
	 * @return bool
	 */
	public function isReadable($user_groups)
	{
		return ($this->id > 0) && ($this->get('content_status') >= 5) && $this->isAllowedForGroups($user_groups);
	}

	/**
	 * @param array $user_groups
	 * @return bool
	 */
	public function isEditable($user_groups)
	{
		return ($this->id > 0) && $this->isAllowedForGroups($user_groups);
	}

	/**
	 * @return bool
	 */
	public function isNewAllowed()
	{
		return (!$this->id);
	}

	/**
	 * @param string $default_lang
	 * @param \Orange\Portal\Core\Model\User|null $access_user
	 * @return array
	 */
	public function getLanguagePages($default_lang, $access_user = null)
	{
		$links = [];
		$id_def = (empty($this->get('content_lang')) || ($this->get('content_lang') == $default_lang))
			? $this->id
			: $this->get('content_default_lang_id');
		if ($id_def) {
			$select = new \Orange\Database\Queries\Select(static::$table);
			$select->addWhereBracket(true);
			$select->addWhereBracket(true);
			$select->addWhere(new Condition('content_lang', 'LIKE', $default_lang));
			$select->addWhere(new Condition('id', '=', $id_def));
			$select->addWhereBracket(false);
			$select->addWhereOperator(Condition::L_OR);
			$select->addWhereBracket(true);
			$select->addWhere(new Condition('content_lang', 'LIKE', ''));
			$select->addWhere(new Condition('id', '=', $id_def));
			$select->addWhereBracket(false);
			$select->addWhereOperator(Condition::L_OR);
			$select->addWhereBracket(true);
			$select->addWhere(new Condition('content_lang', 'NOT LIKE', $default_lang));
			$select->addWhere(new Condition('content_default_lang_id', '=', $id_def));
			$select->addWhereBracket(false);
			$select->addWhereBracket(false);
			if ($pages = self::getList([
				'access_user' => $access_user,
				'status_min' => Content::STATUS_ENABLED,
			], get_class($this), $select)
			) {
				foreach ($pages as $page) {
					$links[$page->get('content_lang')] = $page;
				}
			}
		}
		return $links;
	}

	/**
	 * @param int $root
	 * @param array $order
	 * @param string $group_field
	 * @param \Orange\Portal\Core\Model\User $access_user
	 * @return array
	 */
	public static function reorder($root, $order, $group_field, $access_user)
	{
		$updated = [];
		if ($order) {
			if ($list = static::getList(['IDs' => $order, 'access_user' => $access_user], __CLASS__)) {
				foreach ($order as $ord => $id) {
					if (isset($list[$id])) {
						$item = $list[$id];
						if (($item->get('content_order') != $ord) || ($item->get($group_field) != $root)) {
							$item->set('content_order', $ord);
							$item->set($group_field, $root);
							$item->save();
							$updated[] = $item->id;
						}
					}
				}
			}
		}
		return $updated;
	}

	/**
	 * @param \Orange\Portal\Core\Model\Content[] $list
	 * @return array
	 */
	public static function getRssData($list)
	{
		$rss = [];
		if ($list) {
			foreach ($list as $item) {
				$image = new \OPMM_System_Media($item->get('content_image'));
				$rss[] = [
					'title' => $item->get('content_title'),
					'link' => $item->getURL(),
					'time' => $item->get('content_time_published'),
					'image_url' => $image->id ? $image->getURL('m') : '',
					'image_type' => $image->id ? $image->getMimeType() : '',
				];
			}
		}
		return $rss;
	}

}