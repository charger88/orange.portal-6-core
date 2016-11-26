<?php

namespace Orange\Portal\Core\App;

/**
 * Trait Request
 */
trait Request
{

	/**
	 * @var string|null
	 */
	private static $ip = null;

	/**
	 * @param $name
	 * @param mixed $default
	 * @return mixed
	 */
	protected function getGet($name, $default = null)
	{
		return isset($_GET[$name]) ? $_GET[$name] : $default;
	}

	/**
	 * @param $name
	 * @param mixed $default
	 * @return mixed
	 */
	protected function getPost($name, $default = null)
	{
		return isset($_POST[$name]) ? $_POST[$name] : $default;
	}

	/**
	 * @return array
	 */
	protected function getGetArray()
	{
		return $_GET;
	}

	/**
	 * @return array
	 */
	protected function getPostArray()
	{
		return $_POST;
	}

	/**
	 * @return string
	 */
	protected function getPostRaw()
	{
		return file_get_contents('php://input');
	}

	/**
	 * @param $name
	 * @param string|null $default
	 * @return string|null
	 */
	protected function getServer($name, $default = null)
	{
		return isset($_SERVER[$name]) ? $_SERVER[$name] : $default;
	}

	/**
	 * @param string $name
	 * @return array|null
	 */
	protected function getFile($name)
	{
		return isset($_FILES[$name]) ? $_FILES[$name] : null;
	}

	/**
	 * @param $name
	 * @param string|null $default
	 * @return string|null
	 */
	protected function getCookie($name, $default = null)
	{
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	protected function setCookie($name, $value)
	{
		$_COOKIE[$name] = $value;
		setcookie($name, $value, strtotime('+60 days'));
	}

	/**
	 * @return string
	 */
	protected function getIP()
	{
		if (is_null(self::$ip)) {
			self::$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
			if (isset($_SERVER['X-FORWARDED-FOR']) && in_array(self::$ip, Portal::config('system_proxy_ip', []))) {
				self::$ip = $_SERVER['X-FORWARDED-FOR'];
			}
		}
		return self::$ip;
	}

	/**
	 * @return string
	 */
	protected function getURI()
	{
		return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
	}

	/**
	 * @return string
	 */
	protected function getHTTPReferer()
	{
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	}

	/**
	 * @return string
	 */
	protected function getUserAgent()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	}

}