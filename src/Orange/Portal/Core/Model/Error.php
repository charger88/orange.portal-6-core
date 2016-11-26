<?php

namespace Orange\Portal\Core\Model;

/**
 * Class Error
 */
class Error extends Content
{

	/**
	 * @return int|null
	 */
	public function save()
	{
		$this->set('content_type', 'error');
		$this->set('content_template', 'main-error.phtml');
		return parent::save();
	}

}