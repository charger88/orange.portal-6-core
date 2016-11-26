<?php

namespace Orange\Portal\Core\Session;

interface SessionInterface
{

	/**
	 * Start session
	 * @return boolean
	 */
	public function start();

	/**
	 * Close session
	 * @return boolean
	 */
	public function close();

	/**
	 * Destroy session
	 * @return boolean
	 */
	public function destroy();

	/**
	 * Destroy session
	 * @return string
	 */
	public function id();

	/**
	 * @param $name
	 * @param string|null $default
	 * @return string|null
	 */
	public function get($name, $default = null);

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function set($name, $value);

	/**
	 * @return boolean
	 */
	public function cookieExists();

	/**
	 * @return boolean|null
	 */
	public function destroyAll();

}