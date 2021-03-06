<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette
 * @version    $Id: Configurator.php 281 2009-04-18 02:02:35Z david@grudl.com $
 */



require_once dirname(__FILE__) . '/Object.php';



/**
 * Nette\Environment helper.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette
 */
class Configurator extends Object
{
	/** @var string */
	public $defaultConfigFile = '%appDir%/config.ini';

	/** @var array */
	public $defaultServices = array(
		'Nette\Application\Application' => 'Nette\Application\Application',
		'Nette\Web\IHttpRequest' => 'Nette\Web\HttpRequest',
		'Nette\Web\IHttpResponse' => 'Nette\Web\HttpResponse',
		'Nette\Web\IUser' => 'Nette\Web\User',
		'Nette\Caching\ICacheStorage' => array(__CLASS__, 'createCacheStorage'),
		'Nette\Web\Session' => 'Nette\Web\Session',
	);



	/**
	 * Detect environment mode.
	 * @param  string mode name
	 * @return bool
	 */
	public function detect($name)
	{
		switch ($name) {
		case 'environment':
			// environment name autodetection
			if ($this->detect('console')) {
				return Environment::CONSOLE;

			} else {
				return Environment::getMode('production') ? Environment::PRODUCTION : Environment::DEVELOPMENT;
			}

		case 'production':
			// detects production mode by server IP address
			if (PHP_SAPI === 'cli') {
				return FALSE;

			} elseif (isset($_SERVER['SERVER_ADDR']) || isset($_SERVER['LOCAL_ADDR'])) {
				$addr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];
				$oct = explode('.', $addr);
				// 10.0.0.0/8   Private network
				// 127.0.0.0/8  Loopback
				// 169.254.0.0/16 & ::1  Link-Local
				// 172.16.0.0/12  Private network
				// 192.168.0.0/16  Private network
				return $addr !== '::1' && (count($oct) !== 4 || ($oct[0] !== '10' && $oct[0] !== '127' && ($oct[0] !== '172' || $oct[1] < 16 || $oct[1] > 31)
					&& ($oct[0] !== '169' || $oct[1] !== '254') && ($oct[0] !== '192' || $oct[1] !== '168')));

			} else {
				return TRUE;
			}

		case 'debug':
			// Determines whether the debugger is active
			if (defined('DEBUG_MODE')) {
				return (bool) DEBUG_MODE;

			} else {
				return !Environment::getMode('production') && isset($_REQUEST['DBGSESSID']);
				// function_exists('DebugBreak');
			}

		case 'console':
			return PHP_SAPI === 'cli';

		default:
			// unknown mode
			return NULL;
		}
	}



	/**
	 * Loads global configuration from file and process it.
	 * @param  string|Nette\Config\Config  file name or Config object
	 * @return Config
	 */
	public function loadConfig($file)
	{
		$name = Environment::getName();

		if ($file instanceof Config) {
			$config = $file;
			$file = NULL;

		} else {
			if ($file === NULL) {
				$file = $this->defaultConfigFile;
			}
			$file = Environment::expand($file);
			$config = Config::fromFile($file, $name, 0);
		}

		// process environment variables
		if ($config->variable instanceof Config) {
			foreach ($config->variable as $key => $value) {
				Environment::setVariable($key, $value);
			}
		}

		$config->expand();

		// process services
		$locator = Environment::getServiceLocator();
		if ($config->service instanceof Config) {
			foreach ($config->service as $key => $value) {
				$locator->addService($value, strtr($key, '-', '\\'));
			}
		}

		// check temporary directory - TODO: discuss
		/*
		$dir = Environment::getVariable('tempDir');
		if ($dir && !(is_dir($dir) && is_writable($dir))) {
			trigger_error("Temporary directory '$dir' is not writable", E_USER_NOTICE);
		}
		*/

		// process ini settings
		if ($config->set instanceof Config) {
			if (PATH_SEPARATOR !== ';' && isset($config->set->include_path)) {
				$config->set->include_path = str_replace(';', PATH_SEPARATOR, $config->set->include_path);
			}

			foreach ($config->set as $key => $value) {
				$key = strtr($key, '-', '.'); // old INI compatibility

				if (!is_scalar($value)) {
					throw new InvalidStateException("Configuration value for directive '$key' is not scalar.");
				}

				if (function_exists('ini_set')) {
					ini_set($key, $value);
				} else {
					switch ($key) {
					case 'include_path':
						set_include_path($value);
						break;
					case 'iconv.internal_encoding':
						iconv_set_encoding('internal_encoding', $value);
						break;
					case 'mbstring.internal_encoding':
						mb_internal_encoding($value);
						break;
					case 'date.timezone':
						date_default_timezone_set($value);
						break;
					case 'error_reporting':
						error_reporting($value);
						break;
					case 'ignore_user_abort':
						ignore_user_abort($value);
						break;
					case 'max_execution_time':
						set_time_limit($value);
						break;
					default:
						if (ini_get($key) != $value) { // intentionally ==
							throw new NotSupportedException('Required function ini_set() is disabled.');
						}
					}
				}
			}
		}

		// define constants
		if ($config->const instanceof Config) {
			foreach ($config->const as $key => $value) {
				define($key, $value);
			}
		}

		// set modes
		if (isset($config->mode)) {
			foreach($config->mode as $mode => $state) {
				Environment::setMode($mode, $state);
			}
		}

		$config->setReadOnly();
		return $config;
	}



	/********************* service factories ****************d*g**/



	/**
	 * Get initial instance of service locator.
	 * @return IServiceLocator
	 */
	public function createServiceLocator()
	{
		$locator = new ServiceLocator;
		foreach ($this->defaultServices as $name => $service) {
			$locator->addService($service, $name);
		}
		return $locator;
	}



	/**
	 * @return ICacheStorage
	 */
	public static function createCacheStorage()
	{
		return new FileStorage(Environment::getVariable('cacheBase'));
	}

}
