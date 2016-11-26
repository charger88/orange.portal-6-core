<?php

namespace Orange\Portal\Core\Model;

/**
 * Class ContentText
 */
class ContentText extends \Orange\Database\ActiveRecord
{

	const FORMAT_AS_IS = 0;
	const FORMAT_AUTOFORMAT = 1;
	const FORMAT_PLAIN_TEXT = 2;
	const FORMAT_SAFE_HTML = 3;
	const FORMAT_COMMENTS_MODE = 4;

	/**
	 * @var string
	 */
	protected static $table = 'content_text';

	/**
	 * @var array
	 */
	protected static $scheme = [
		'id' => ['type' => 'ID'],
		'content_id' => ['type' => 'INTEGER'],
		'content_text_role' => ['type' => 'STRING', 'length' => 16],
		'content_text_format' => ['type' => 'TINYINT'],
		'content_text_value' => ['type' => 'LONGTEXT'],
	];

	/**
	 * @var array
	 */
	protected static $keys = ['content_id'];

	/**
	 * @var array
	 */
	protected static $uniq = [['content_id', 'content_text_role']];

	public function format()
	{
		switch ($this->get('content_text_format')) {
			case self::FORMAT_AS_IS:
				$text = $this->get('content_text_value');
				break;
			case self::FORMAT_AUTOFORMAT:
				$text = nl2br($this->get('content_text_value'));
				break;
			case self::FORMAT_PLAIN_TEXT:
				$text = \Orange\Filters\SimpleFilters::escAsText($this->get('content_text_value'));
				break;
			case self::FORMAT_SAFE_HTML:
				$text = (new \Orange\Filters\HtmlFilter())->parse($this->get('content_text_value'));
				break;
			case self::FORMAT_COMMENTS_MODE:
				$text = \Orange\Filters\SimpleFilters::esc($this->get('content_text_value'));
				$text = \Orange\Filters\SimpleFilters::escAsTextWithQuotes($text);
				break;
			default:
				throw new \Exception('Unknown format type: ' . $this->get('content_text_format'));
				break;
		}
		$replaces = [
			'%%url%%' => OP_WWW,
			'%%sitename%%' => \Orange\Portal\Core\App\Portal::config('system_sitename'),
		];
		foreach ($replaces as $code => $value) {
			$text = str_replace($code, $value, $text);
		}
		return $text;
	}

}