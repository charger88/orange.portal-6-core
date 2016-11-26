<?php

namespace Orange\Portal\Core\Net;

class Downloader
{

	public static function download($url, $timeout = 30)
	{
		if (function_exists('curl_exec')) {
			$curlRes = curl_init($url);
			curl_setopt($curlRes, CURLOPT_RETURNTRANSFER, true);
			//curl_setopt($curlRes, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curlRes, CURLOPT_LOW_SPEED_LIMIT, 128);
			curl_setopt($curlRes, CURLOPT_LOW_SPEED_TIME, 5);
			curl_setopt($curlRes, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($curlRes, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($curlRes, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.5 (KHTML, like Gecko) Chrome/19.0.1084.56 Safari/536.5');
			curl_setopt($curlRes, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curlRes, CURLOPT_SSL_VERIFYHOST, 0);
			$data = curl_exec($curlRes);
			if ($data) {
				curl_close($curlRes);
				return $data;
			} else {
				return false;
			}
		} else {
			return file_get_contents($url);
		}
	}

}