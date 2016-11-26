<?php

namespace Orange\Portal\Core\App;

class Templater
{

	/**
	 * @var Theme
	 */
	public $theme;
	private $assigned = [];
	private $template;

	public function __construct($theme_name = null)
	{
		$this->theme = new Theme($theme_name);
	}

	public function assign($p1, $p2 = null)
	{
		if (is_null($p2) && is_array($p1)) {
			foreach ($p1 as $key => $value) {
				$this->assigned[$key] = $value;
			}
		} else {
			$this->assigned[$p1] = $p2;
		}
		return $this;
	}

	public function clear()
	{
		$this->assigned = [];
	}

	public function fetch($template = '', $vars = null)
	{
		if ($this->template = $this->getTemplateFilename($template)) {
			$vars = (!is_null($vars) && is_array($vars)) ? array_merge($this->assigned, $vars) : $this->assigned;
			if ($vars) {
				if (isset($vars['this'])) {
					unset($vars['this']);
				}
				extract($vars, EXTR_OVERWRITE);
			}
			ob_start();
			include $this->template;
			$text = ob_get_contents();
			ob_end_clean();
			return $text;
		} else {
			return '';
		}
	}

	private function getTemplateFilename($template)
	{
		$templateFilename = null;
		if (strpos($template, '/') > 0) {
			$template = explode('/', $template);
			if ($this->checkFilename($template[0]) && $this->checkFilename($template[1])) {
				$templatePath = 'templates/modules/' . $template[0] . '/';
				foreach ($this->theme->folders as $folder) {
					if (is_file(OP_SYS_ROOT . 'themes/' . $folder . '/' . $templatePath . $template[1])) {
						$templateFilename = OP_SYS_ROOT . 'themes/' . $folder . '/' . $templatePath . $template[1];
						break;
					}
				}
				if (is_null($templateFilename)) {
					if (is_file(OP_SYS_ROOT . 'modules/' . $template[0] . '/templates/' . $template[1])) {
						$templateFilename = OP_SYS_ROOT . 'modules/' . $template[0] . '/templates/' . $template[1];
					}
				}
			}
		} else {
			if ($this->checkFilename($template)) {
				if (strpos($template, 'form-') === 0) {
					$templatePath = 'templates/form/';
				} else if ((strpos($template, 'main-') === 0) || (strpos($template, 'block-') === 0) || (strpos($template, 'area-') === 0)) {
					$templatePath = 'templates/';
				} else {
					$templatePath = 'templates/html/';
				}
				foreach ($this->theme->folders as $folder) {
					if (is_file(OP_SYS_ROOT . 'themes/' . $folder . '/' . $templatePath . $template)) {
						$templateFilename = OP_SYS_ROOT . 'themes/' . $folder . '/' . $templatePath . $template;
						break;
					}
				}
			}
		}
		return $templateFilename;
	}

	private function checkFilename($filename)
	{
		return !empty($filename) && (strpos($filename, '/') === false) && (strpos($filename, '\\') === false) && ($filename{0} != '.');
	}

	public function esc($text, $nl2br = false)
	{
		$text = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
		return $nl2br ? nl2br($text) : $text;
	}

	public function ee($text, $nl2br = false)
	{
		$text = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
		echo $nl2br ? nl2br($text) : $text;
	}

	public function el($key, $array_key = null)
	{
		echo Lang::t($key, $array_key);
	}

	public function getDate($date)
	{
		if (!is_numeric($date)) {
			$date = strtotime($date);
		}
		return date(Portal::config('system_date_format', 'd.m.Y'), $date);
	}

	public function getTime($time)
	{
		if (!is_numeric($time)) {
			$time = strtotime($time);
		}
		return date(Portal::config('system_time_format', 'd.m.Y H:i:s'), $time);
	}

	public function getFilesize($size)
	{
		$size = intval($size);
		if ($size < 1) {
			$output = '0';
		} else if ($size < 512) {
			$output = $this->formatFilesize($size) . ' b';
		} else if ($size < 512 * 1024) {
			$output = $this->formatFilesize($size / 1024) . ' kB';
		} else if ($size < 512 * 1024 * 1024) {
			$output = $this->formatFilesize($size / 1024 / 1024) . ' MB';
		} else if ($size < 512 * 1024 * 1024) {
			$output = $this->formatFilesize($size / 1024 / 1024 / 1024) . ' GB';
		} else {
			$output = $this->formatFilesize($size / 1024 / 1024 / 1024 / 1024) . ' TB';
		}
		return $output;
	}

	private function formatFilesize($size)
	{
		if ($size - round($size)) {
			return sprintf("%.1f", $size);
		} else {
			return $size;
		}
	}
}
