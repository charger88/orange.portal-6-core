<?php

namespace Orange\Portal\Core\Session;

class Native implements SessionInterface
{

	/**
	 * Start session
	 * @return boolean
	 */
	public function start()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_set_cookie_params(2592000, '/');
			ini_set('session.gc_maxlifetime', 2592000);
			ini_set('session.name', 'OPSESSION');
			ini_set('session.cookie_httponly', 'On');
			ini_set('session.hash_function', 'sha256');
			$status = session_start();
			session_regenerate_id();
			return $status;
		}
		return true;
	}

	/**
	 * Close session
	 * @return boolean
	 */
	public function close()
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_commit();
		}
		return true;
	}

	/**
	 * Destroy session
	 * @return boolean
	 */
	public function destroy()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			$this->start();
		}
		if (session_status() === PHP_SESSION_ACTIVE) {
			return session_destroy();
		} else {
			return false;
		}
	}

	/**
	 * Destroy session
	 * @return string
	 */
	public function id()
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			return session_id();
		}
		return '';
	}

	/**
	 * @param $name
	 * @param string|null $default
	 * @return string|null
	 */
	public function get($name, $default = null)
	{
		$this->start();
		return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
	}

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function set($name, $value)
	{
		$this->start();
		$_SESSION[$name] = $value;
	}

	/**
	 * @return boolean
	 */
	public function cookieExists()
	{
		return !empty($_COOKIE['OPSESSION']);
	}

	/**
	 * Method is not implemented
	 * @return boolean|null
	 */
	public function destroyAll()
	{
		return null;
	}
}