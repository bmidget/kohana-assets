<?php defined('SYSPATH') or die('No direct script access.');

require Kohana::find_file('vendor', 'lessphp/lessc.inc');
require Kohana::find_file('vendor', 'jsmin/jsmin');

class Assets_Core {

	/**
	 * Where to save compiled css and js files
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $_compile_paths = array();

	/**
	 * The array from assets config file
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $_config = array();

	/**
	 * Name of the assets instance
	 * 
	 * (default value: 'core')
	 * 
	 * @var string
	 * @access protected
	 */
	protected $_name = 'core';

	/**
	 * Default directories to import from
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $_import_dirs = array();

	/**
	 * Arrays containing all the added paths for js and css/less
	 * that will be compiled together
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_paths = array
	(
		'css' => array(),
		'js'  => array(),
	);

	
	/**
	 * Factory method returns new assets object
	 * 
	 * @access public
	 * @static
	 * @param mixed $name (default: null)
	 * @return object
	 */
	public static function factory($name = null)
	{
		return new Assets($name);
	}


	/**
	 * Assets __construct.
	 * 
	 * @access public
	 * @param mixed $name (default: null)
	 * @return object
	 */
	public function __construct($name = null)
	{
		$this->_config = Kohana::$config->load('assets');

		if ($name !== null)
		{
			$this->_name = $name;
		}

		$this->_compile_paths = Arr::get($this->_config, 'compile_paths');
	}


	/**
	 * Add css paths or an array of paths for compiling
	 * 
	 * @access public
	 * @param mixed $key
	 * @param mixed $path (default: null)
	 * @return object
	 */
	public function css($key, $path = null)
	{
		if (is_array($key))
		{
			foreach ($key as $_key => $_value)
			{
				$this->css($_key, $_value);
			}
		}
		else
		{
			$this->_paths['css'][$key] = $path;
		}

		return $this;
	}


	/**
	 * Get tag for ether 'css' or 'js'
	 * 
	 * @access public
	 * @param mixed $type
	 * @return string
	 */
	public function get($type)
	{
		$hash = $this->_make_hash($type);

		if ( ! $hash)
		{
			return;
		}

		$compile_path = $this->_get_compile_path($type, $hash);

		if (file_exists($compile_path))
		{
			return $this->_get_tag($type, $hash);
		}

		$contents = '';
		foreach ($this->_paths[$type] as $key => $value)
		{
			$file_location = $this->_get_file_location($type, $value);

			$contents.= file_get_contents($file_location);
		}

		$compiled_contents = $this->{'_compile_'.$type}($contents);

		$this->_remove_files($type, $hash);
		$this->_write_file($compile_path, $compiled_contents);

		return $this->_get_tag($type, $hash);
	}


	/**
	 * Add import directories for LESS compiling
	 * 
	 * @access public
	 * @param mixed $path
	 * @return object
	 */
	public function import_dirs($path)
	{
		if (is_array($path))
		{
			foreach ($path as $_path)
			{
				$this->import_dirs($_path);
			}
			
			return $this;
		}
		else
		{
			$this->_import_dirs[] = $path;

			return $this;
		}
	}


	/**
	 * Add js paths or an array of paths for compiling
	 * 
	 * @access public
	 * @param mixed $key
	 * @param mixed $path (default: NULL)
	 * @return object
	 */
	public function js($key, $path = NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $_key => $_value)
			{
				$this->js($_key, $_value);
			}

			return $this;
		}
		else
		{
			$this->_paths['js'][$key] = $path;
		}

		return $this;
	}


	/**
	 * Set the asset object name
	 * 
	 * @access public
	 * @param mixed $name
	 * @return object
	 */
	public function name($name)
	{
		$this->_name = $name;

		return $this;
	}


	/**
	 * Remove a specific css or js path or array of paths from added paths
	 * 
	 * @access public
	 * @param mixed $type
	 * @param mixed $key
	 * @return object
	 */
	public function remove($type, $key = null)
	{
		if (is_array($type))
		{
			foreach ($type as $key => $value)
			{
				$this->remove($key, $value);
			}

			return $this;
		}
		elseif (isset($this->_paths[$type][$key]))
		{
			unset($this->_paths[$type][$key]);
		}

		return $this;
	}

	/**
	 * Compile into a single string using PHPLess
	 * 
	 * @access protected
	 * @param mixed $contents
	 * @return string
	 */
	protected function _compile_css($contents)
	{
		$lessc = new lessc;
		if ( ! empty($this->_import_dirs))
		{
			$lessc->importDir = $this->_import_dirs;
		}
		$css = $lessc->parse($contents);

		return $css;
	}


	/**
	 * Compile JS into a single string using JSMin
	 * 
	 * @access protected
	 * @param mixed $contents
	 * @return string
	 */
	protected function _compile_js($contents)
	{
		$mini_js = JSMin::minify($contents);
		return $mini_js;
	}


	/**
	 * Get the path to compile to
	 * 
	 * @access protected
	 * @param mixed $type
	 * @param mixed $hash
	 * @return string
	 */
	protected function _get_compile_path($type, $hash)
	{
		$compile_path = DOCROOT.$this->_compile_paths[$type].$hash.'.'.$type;

		return $compile_path;
	}

	/**
	 * Retrieve the file location for a pre-compiled js/css/less asset
	 * 
	 * @access protected
	 * @param mixed $type
	 * @param mixed $path
	 * @return string
	 */
	protected function _get_file_location($type, $path)
	{
		if (strpos($path, '/') === 0 OR strpos($path, 'http') === 0)
		{
			$file_location = $path;
		}
		else
		{
			$locations = $this->_config['pre_compile_dirs'];

			if ($locations[$type] instanceof Closure)
			{
				$file_location = $locations[$type]($path);
			}
			else
			{
				$file_location = $locations[$type].$path;
			}
		}

		return $file_location;
	}


	/**
	 * Retrieve a namespace from the compiled file
	 * This namespace is the prefix before the '_' in the compiled file name:
	 * ex: 627a74de1fc5a2e750e85a2995a5ac6a332e5018_70e79492f9153932dea604732a27f5145821876c.js
	 * 
	 * @access protected
	 * @param mixed $type
	 * @return string
	 */
	protected function _get_file_namespace($type)
	{
		$array = $this->_paths[$type];

		$str = '';
		foreach ($array as $path)
		{
			$str.= $path;
		}

		$name = sha1($this->_name.$path);

		return $name;
	}

	/**
	 * Retrieve the path that the style or script HTML tag will point to
	 * 
	 * @access protected
	 * @param mixed $type
	 * @param mixed $hash
	 * @return string
	 */
	protected function _get_link_path($type, $hash)
	{
		return $this->_compile_paths[$type].$hash.'.'.$type;
	}

	/**
	 * Build and return the style or script HTML tag
	 * 
	 * @access protected
	 * @param mixed $type
	 * @param mixed $hash
	 * @return string
	 */
	protected function _get_tag($type, $hash)
	{
		$link = $this->_get_link_path($type, $hash);

		if ($type == 'css')
		{
			return HTML::style($link)."\n";
		}

		if ($type == 'js')
		{
			return HTML::script($link)."\n";
		}
	}

	/**
	 * Make a hash that is the represents a namespace for all the assets included in the compiled file
	 * and a last edited date of those files.
	 * The format for this hash is {namespace}_{edited}.ext
	 * ex: 627a74de1fc5a2e750e85a2995a5ac6a332e5018_70e79492f9153932dea604732a27f5145821876c.js
	 * 
	 * @access protected
	 * @param mixed $type
	 * @return string
	 */
	protected function _make_hash($type)
	{
		$array = $this->_paths[$type];

		$str = '';
		foreach ($array as $path)
		{
			$file_location = $this->_get_file_location($type, $path);

			$str.= filemtime($file_location);
		}

		$hash = $str
			? $this->_get_file_namespace($type).'_'.sha1($str)
			: NULL;

		return $hash;
	}

	/**
	 * Remove files fromt he compiled directory for the same set of assets that
	 * are outdated do to edits
	 * 
	 * @access protected
	 * @param mixed $type
	 * @param mixed $hash
	 * @return void
	 */
	protected function _remove_files($type, $hash)
	{
		$base = DOCROOT.$this->_compile_paths[$type];

		$files = glob($base.$this->_get_file_namespace($type).'_*.'.$type);

		foreach ($files as $file)
		{
			unlink($file);
		}
	}

	/**
	 * Write the compiled string to the compiled directory
	 * 
	 * @access protected
	 * @param mixed $path
	 * @param mixed $contents
	 * @return void
	 */
	protected function _write_file($path, $contents)
	{
		file_put_contents($path, $contents);
	}

}