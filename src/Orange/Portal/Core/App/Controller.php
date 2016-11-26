<?php

namespace Orange\Portal\Core\App;

/**
 * Class Controller
 */
class Controller
{

	use Request;

	const STATUS_ALERT = -1;
	const STATUS_ERROR = 0;
	const STATUS_WARNING = 1;
	const STATUS_NOTFOUND = 2;
	const STATUS_INFO = 3;
	const STATUS_OK = 4;
	const STATUS_COMPLETE = 5;

	/**
	 * @var Orange\Portal\Core\Model\Content
	 */
	protected $content;

	/**
	 * @var Orange\Portal\Core\Model\User
	 */
	protected $user;

	/**
	 * @var Orange\Portal\Core\Session\SessionInterface
	 */
	protected $session;

	/**
	 * @var Templater
	 */
	protected $templater;

	/**
	 * @var array
	 */
	protected $args = [];

	/**
	 * @var array
	 */
	protected $cachemap = [];

	/**
	 * @param Orange\Portal\Core\Model\Content $content
	 * @param Orange\Portal\Core\Model\User $user
	 * @param Orange\Portal\Core\Session\SessionInterface $session
	 * @param Templater $templater
	 * @param array $args
	 */
	public function __construct($content, $user, $session, $templater, $args = [])
	{
		$this->content = $content;
		$this->user = $user;
		$this->session = $session;
		$this->templater = $templater;
		$this->args = $args;
		foreach ($this->args as $key => $value) {
			if ($value && ($value{0} == '@')) {
				$this->args[$key] = $this->content->get(substr($value, 1));
			}
		}
	}

	/**
	 * @param string $param
	 * @param string | boolean | integer | float $default
	 * @return string | boolean | integer | float
	 */
	protected function arg($param, $default = null)
	{
		return isset($this->args[$param]) ? $this->args[$param] : $default;
	}

	/**
	 * @param string $template
	 * @param string $html
	 * @param array $data
	 * @return string|null
	 */
	protected function wrapContentWithTemplate($template, $html = '', $data = [])
	{
		$data['html'] = $html;
		return $this->templater->fetch($template, $data);
	}

	/**
	 * @param $message
	 * @param $status
	 * @param string|null $redirect
	 * @param array|null $data
	 * @param bool $ignoreajax
	 * @return array|string
	 */
	protected function msg($message, $status, $redirect = null, $data = null, $ignoreajax = false)
	{
		$msg_data = ['message' => $message, 'status' => $status, 'redirect' => $redirect];
		if (!is_null($data)) {
			$msg_data = is_array($data) ? array_merge($msg_data, $data) : array_merge($msg_data, ['html' => $data]);
		}
		if (Portal::env('cli', false)) {
			return $msg_data['message'];
		} else {
			return Portal::env('ajax', false) && !$ignoreajax ? $msg_data : $this->templater->fetch('message.phtml', $msg_data);
		}
	}

	/**
	 * @param string $url
	 * @param bool $permanent
	 * @return bool
	 */
	protected function redirect($url, $permanent = false)
	{
		header($this->getServer('SERVER_PROTOCOL') . ($permanent ? ' 301 Moved Permanently' : ' 302 Found'));
		header('Location: ' . $url);
		die();
		return true;
	}

	/**
	 * @param $message
	 * @param array $vars
	 * @param string $log_name
	 * @param int $status
	 * @param \Orange\Database\ActiveRecord|null $object
	 */
	protected function log($message, $vars = [], $log_name = 'LOG_MISC', $status = self::STATUS_INFO, $object = null)
	{
		$log = new \Orange\Portal\Core\Model\Log();
		$log->set('log_log', $log_name);
		$log->set('log_status', $status);
		$log->set('log_time', time());
		$log->set('log_uri', $this->getURI());
		$log->set('log_ip', $this->getIP());
		$log->set('log_useragent', $this->getUserAgent());
		$log->set('log_user_id', $this->user ? $this->user->id : 0);
		$log->set('log_classname', is_object($object) ? get_class($object) : '');
		$log->set('log_object_id', is_object($object) ? $object->id : 0);
		$log->set('log_message', $message);
		$log->set('log_vars', $vars);
		if (!$log->save() || ($status == self::STATUS_ALERT)) {
			$log->send(Portal::config('system_email_system', 'webmaster@' . Portal::config('domain', 'localhost')));
		}
	}

	/**
	 * @param string $methodname
	 * @return bool
	 */
	public function isMethodCacheable($methodname)
	{
		return isset($this->cachemap[$methodname]) && ((strpos($methodname, 'Block') !== false) || !$this->getPostArray());
	}

	/**
	 * @param string $methodname
	 * @param array $request
	 * @return string
	 */
	private function getMethodKey($methodname, $request)
	{
		if (isset($this->cachemap[$methodname])) {
			$map = $this->cachemap[$methodname];
			$key = [];
			$key[] = str_replace('_', '-', strtolower(get_class($this)));
			$key[] = strtolower($methodname);
			if (in_array('id_is_page_id', $map)) {
				$key[] = Portal::getInstance()->content->id . '_PID';
			}
			if (in_array('id_is_content_id', $map)) {
				$key[] = $this->content->id . '_PID';
			}
			if (in_array('id_is_first_argument', $map)) {
				$key[] = (isset($request[0]) ? intval($request[0]) : 0) . '_IDF';
			}
			if (isset($map['id_is_arg'])) {
				$key[] = intval($this->arg($map['id_is_arg'], 0)) . '_IDA';
			}
			if (in_array('by_user_access', $map)) {
				$key[] = intval($this->user->get('user_status')) . '-US';
				$key[] = implode('-', $this->user->get('user_groups') ? $this->user->get('user_groups') : [0]) . '-UG';
			}
			if (in_array('by_user_id', $map)) {
				$key[] = $this->user->id . '-UI';
			}
			if (in_array('by_content_id', $map)) {
				$key[] = $this->content->id . '-CI';
			}
			if (in_array('by_page_id', $map)) {
				$key[] = Portal::getInstance()->content->id . '-PI';
			}
			if ($request) {
				$key[] = md5(implode(';', $request)) . '-REQ';
			}
			if (in_array('by_date', $map)) {
				$key[] = date("Ymd");
			}
			if ($this->args) {
				$key[] = md5(http_build_query($this->args)) . '-CA';
			}
			$key[] = ($this->content->get('content_lang') ? $this->content->get('content_lang') : 'xx');
			$key[] = Portal::env('protocol');
			return implode('_', $key);
		} else {
			return '';
		}
	}

	/**
	 * @param string $methodname
	 * @param array $request
	 * @return string|null
	 */
	public function getMethodCache($methodname, $request)
	{
		if ($key = $this->getMethodKey($methodname, $request)) {
			return Portal::getInstance()->cache->get($key);
		} else {
			return null;
		}
	}

	/**
	 * @param string $methodname
	 * @param array $request
	 * @param string $data
	 * @return bool
	 */
	public function setMethodCache($methodname, $request, $data)
	{
		if ($key = $this->getMethodKey($methodname, $request)) {
			$status = Portal::getInstance()->cache->set($key, $data);
			if (!$status) {
				$this->log('CACHE_NOT_SAVED %s', [$key], 'LOG_CACHE', self::STATUS_ALERT);
			}
			return (bool)$status;
		} else {
			return false;
		}
	}

	/**
	 * @param string|null $classname
	 * @param string|null $methodname
	 * @param int|null $id
	 */
	public function deleteMethodCache($classname = null, $methodname = null, $id = null)
	{
		$key_mask[] = str_replace('_', '-', strtolower(!is_null($classname) ? $classname : get_class($this)));
		if (!is_null($methodname)) {
			$key_mask[] = strtolower($methodname);
		}
		if (!is_null($id)) {
			$key_mask[] = intval($id);
		}
		Portal::getInstance()->cache->remove(implode('_', $key_mask) . '_', true);
	}

	/**
	 * @param string $message
	 * @param array $vars
	 */
	public function alert($message, $vars = [])
	{
		$this->log($message, $vars, 'LOG_SYSTEM', self::STATUS_ALERT);
	}


}