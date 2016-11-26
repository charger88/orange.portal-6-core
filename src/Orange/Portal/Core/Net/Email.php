<?php

namespace Orange\Portal\Core\Net;

class Email
{

	public $subject = '';
	public $headers = [];
	public $plain_text = null;
	public $html = null;

	public function __construct()
	{
		$this->setCharset("UTF-8");
	}

	private function setCharset($charset)
	{
		$this->headers[] = "Content-Type: text/plain; charset=$charset;";
		$this->headers[] = "MIME-Version: 1.0";
		$this->headers[] = "Content-Transfer-Encoding: 8BIT";
	}

	public function setReturn($email)
	{
		$this->headers[] = "From: $email";
		$this->headers[] = "Reply-To: $email";
	}

	public function send($to)
	{
		return mail(
			$to,
			"=?UTF-8?B?" . base64_encode($this->subject) . "?=",
			!is_null($this->plain_text) ? $this->plain_text : strip_tags($this->html),
			implode(" \r\n", $this->headers)
		);
	}

}