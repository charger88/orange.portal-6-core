<?php

namespace Orange\Portal\Core\Model;

/**
 * Class Admin
 */
class Admin extends Content
{

	/**
	 * @return int|null
	 */
	public function save()
	{
		$this->set('content_type', 'admin');
		$this->set('content_template', 'main-admin.phtml');
		return parent::save();
	}

}