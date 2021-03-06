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
 * @package    Nette\Application
 * @version    $Id: PresenterRequest.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */



require_once dirname(__FILE__) . '/../Object.php';



/**
 * Application presenter request. Immutable object.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Application
 */
final class PresenterRequest extends Object
{
	/** method */
	const FORWARD = 'FORWARD';

	/** @var string */
	private $method;

	/** @var array */
	private $flags = array();

	/** @var string */
	private $name;

	/** @var array */
	private $params;

	/** @var array */
	private $post;

	/** @var array */
	private $files;



	/**
	 * @param  string  fully qualified presenter name (module:module:presenter)
	 * @param  string  method
	 * @param  array   variables provided to the presenter usually via URL
	 * @param  array   variables provided to the presenter via POST
	 * @param  array   all uploaded files
	 */
	public function __construct($name, $method, array $params, array $post = array(), array $files = array(), array $flags = array())
	{
		$this->name = $name;
		$this->method = $method;
		$this->params = $params;
		$this->post = $post;
		$this->files = $files;
		$this->flags = $flags;
	}



	/**
	 * Retrieve the presenter name.
	 * @return string
	 */
	public function getPresenterName()
	{
		return $this->name;
	}



	/**
	 * Returns all variables provided to the presenter (usually via URL).
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}



	/**
	 * Returns all variables provided to the presenter via POST.
	 * @return array
	 */
	public function getPost()
	{
		return $this->post;
	}



	/**
	 * Returns all uploaded files.
	 * @return array
	 */
	public function getFiles()
	{
		return $this->files;
	}



	/**
	 * Checks if the method is the given one.
	 * @param  string
	 * @return bool
	 */
	public function isMethod($method)
	{
		return strcasecmp($this->method, $method) === 0;
	}



	/**
	 * Checks if the method is POST.
	 * @return bool
	 */
	public function isPost()
	{
		return strcasecmp($this->method, 'post') === 0;
	}



	/**
	 * Checks the flag.
	 * @param  string
	 * @return bool
	 */
	public function hasFlag($flag)
	{
		return !empty($this->flags[$flag]);
	}



	/**
	 * @return array
	 * @internal
	 */
	public function modify($var, $key, $value = NULL)
	{
		if (func_num_args() === 3) {
			$this->{$var}[$key] = $value;

		} else {
			$this->$var = $key;
		}
	}

}
