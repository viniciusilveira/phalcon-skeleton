<?php

namespace App\Library;

use Phalcon\DI,
	Phalcon\Mvc\User\Component,
	Phalcon\Translate\Adapter\Csv,
	Phalcon\Translate\AdapterInterface;

/**
 * Language Library
 *
 * @package  App
 * @author   Rian Orie <rian.orie@gmail.com>
 * @category Library
 * @created  2014-11-02
 * @version  1.0
 */
class I18n extends Component
{
	/**
	 * Config storage
	 *
	 * @var array
	 */
	private $config  = [];

	/**
	 * Cache storage
	 *
	 * @var array
	 */
	protected $cache = [];

	/**
	 * instance of self
	 *
	 * @var I18n
	 */
	private static $instance;

	/**
	 * Singleton pattern
	 *
	 * @return I18n
	 */
	public static function instance()
	{
		if (empty(self::$instance)) {
			self::$instance = new I18n;
		}

		return self::$instance;
	}

	/**
	 * Private constructor - disallow to create a new object
	 */
	private function __construct()
	{
		$this->config = DI::getDefault()->getShared('config')->i18n;
	}

	/**
	 * Private clone - disallow to clone the object
	 */
	private function __clone()
	{}

	/**
	 * Set the language
	 *
	 * @param string $lang language code
	 *
	 * @return string
	 */
	public function setLanguage($lang = null)
	{
		// Normalize the language
		if ($lang) {
			$this->config['lang'] = strtolower(str_replace([' ', '-'], '_', $lang));

			$parts = explode('_', str_replace([' ', '-'], '_', $lang));
			if (count($parts) == 1) {
				$parts[1] = $parts[0];
			}

			$this->config['lang'] = strtolower($parts[0]).'_'.strtoupper($parts[1]);
		}

		return $this->config['lang'];
	}

	/**
	 * Load language from the file
	 *
	 * @param string $lang language code
	 *
	 * @return AdapterInterface
	 */
	protected function loadFile($lang)
	{
		// If the translations exist in the cache, load them from there
		if (isset($this->cache[$lang])) {
			return $this->cache[$lang];
		}

		// Sanitize the language code
		$parts = explode('_', str_replace([' ', '-'], '_', $lang));
		if (count($parts) == 1) {
			$parts[1] = $parts[0];
		}
		$lang = strtolower($parts[0]).'_'.strtoupper($parts[1]);

		// Construct the path
		$path = $this->config['dir'].$lang.'.csv';

		// Check it's existence
		if ( ! file_exists(APP_PATH.$path)) {
			throw new \RuntimeException('Failed to load the translations for '.$lang.' in '.$path);
		}

		// Import the actual translations
		$messages = require(APP_PATH.$path);

		// Store them in the cache and return them
		return $this->cache[$lang] = new Csv(['content' => $messages]);
	}

	/**
	 * Get messages from the cache
	 *
	 * @return array
	 */
	public function getCache()
	{
		return $this->cache;
	}

	/**
	 * Translate message
	 *
	 * @param string $string string to translate
	 * @param array  $values replace substrings
	 *
	 * @return string translated string
	 */
	public function _($string, $values = null)
	{
		$arguments = func_get_args();

		// only load the translation file if we're not using the default
		// language to begin with
		if ( ! isset($this->config['default']) || $this->config['lang'] != $this->config['default']) {
			$translate = $this->loadFile($this->config['lang']);
			$string = $translate->query($string);

			$arguments[0] = $string;
		}

		return  (count($arguments) == 1 ? $string : call_user_func_array('sprintf', $arguments));
	}
}