<?php

namespace Orange\Portal\Core\App;

class Lang
{

	private static $lang = [];

	public static function load($folder, $lang_to_load, $baselang = 'en')
	{
		if ($lang_to_load != $baselang) {
			$filename = OP_SYS_ROOT . trim($folder, '/') . '/' . $baselang . '.php';
			if (is_file($filename)) {
				$lang = include $filename;
				self::$lang = array_merge($lang, self::$lang);
			}
		}
		$filename = OP_SYS_ROOT . trim($folder, '/') . '/' . $lang_to_load . '.php';
		if (is_file($filename)) {
			$lang = include $filename;
			self::$lang = array_merge(self::$lang, $lang);
		}
	}

	public static function t($text, $params = [])
	{
		$text = isset(self::$lang[$text]) ? self::$lang[$text] : $text;
		return $params ? vsprintf($text, $params) : $text;
	}

	public static function langs()
	{
		return array_merge([
			'en' => 'English',
			'ru' => 'Russian',
			'af' => 'Afrikaans',
			'sq' => 'Albanian',
			'am' => 'Amharic',
			'ar' => 'Arabic',
			'hy' => 'Armenian',
			'az' => 'Azerbaijani',
			'be' => 'Belarusian',
			'bg' => 'Bulgarian',
			'zh' => 'Chinese',
			'hr' => 'Croatian',
			'cs' => 'Czech',
			'da' => 'Danish',
			'nl' => 'Dutch',
			'eo' => 'Esperanto',
			'et' => 'Estonian',
			'fi' => 'Finnish',
			'fr' => 'French',
			'ka' => 'Georgian',
			'de' => 'German',
			'el' => 'Greek',
			'he' => 'Hebrew',
			'hi' => 'Hindi',
			'hu' => 'Hungarian',
			'id' => 'Indonesian',
			'is' => 'Icelandic',
			'it' => 'Italian',
			'ja' => 'Japanese',
			'kk' => 'Kazakh',
			'ko' => 'Korean',
			'lt' => 'Lithuanian',
			'lv' => 'Latvian',
			'no' => 'Norwegian',
			'fa' => 'Persian (Farsi)',
			'pl' => 'Polish',
			'ps' => 'Pashto, Pushto',
			'pt' => 'Portuguese',
			'ro' => 'Romanian',
			'sr' => 'Serbian',
			'es' => 'Spanish',
			'sv' => 'Swedish',
			'th' => 'Thai',
			'tr' => 'Turkish',
			'uk' => 'Ukrainian',
			'uz' => 'Uzbek',
			'vi' => 'Vietnamese',
		], Portal::config('additional_langs', []));
	}

}