<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * JS and CSS/LESS asset management
 * Compile and serve js, css, and less files
 *
 * @package    Kohana/Assets
 * @author     Ben Midget    https://github.com/bmidget
 * @author     WinterSilence https://github.com/WinterSilence
 * @copyright  (c) 2011-2013 Kohana Team
 * @license    http://kohanaframework.org/license
 */
abstract class Assets_Core {
	
	/**
	 * The array from assets config file
	 *
	 * @var array
	 * @access protected
	 */
	protected $_config = array();
	
	/**
	 * Name of the assets instance
	 *
	 * @var string
	 * @access protected
	 */
	protected $_name = 'core';
	
	/**
	 * Where to save compiled css and js files
	 *
	 * @var array
	 * @access protected
	 */
	protected $_compile_dirs = array();
	
	/**
	 * Default directories to import from
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
	protected $_paths = array(
		'css' => array(),
		'js'  => array(),
	);
	
	/**
	 * Factory method returns new assets object
	 *
	 * @access public
	 * @static
	 * @param mixed $name
	 * @return Assets
	 */
	public static function factory($name = NULL)
	{
		return new Assets($name);
	}
	
	/**
	 * Create access manager
	 *
	 * @access public
	 * @param mixed $name
	 * @return void
	 */
	protected function __construct($name = NULL)
	{
		$this->name($name);
		$this->_config = Kohana::$config->load('assets')->as_array();
		if (isset($this->_config['compile_dirs']))
		{
			$this->_compile_dirs = $this->_config['compile_dirs'];
			unset($this->_config['compile_dirs']);
		}
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
		if ($hash = $this->_make_hash($type))
		{
			$compile_path = $this->_get_compile_path($type, $hash);
			
			if (file_exists($compile_path))
			{
				return $this->_get_tag($type, $hash);
			}
			
			$contents = '';
			foreach ($this->_paths[$type] as $path)
			{
				$contents .= file_get_contents($this->_get_file_location($type, $path));
			}
			
			$this->_remove_files($type, $hash)
				 ->_write_file($compile_path, $this->{'_compile_'.$type}($contents));
			
			return $this->_get_tag($type, $hash);
		}
	}
	
	/**
	 * Add import directories for LESS compiling
	 *
	 * @access public
	 * @param mixed $path
	 * @return Assets
	 */
	public function import_dirs($path)
	{
		if (is_array($path))
		{
			$this->_import_dirs = Arr::merge($this->_import_dirs, $path);
		}
		else
		{
			$this->_import_dirs[] = $path;
		}
		return $this;
	}
	
	/**
	 * Add css paths or an array of paths for compiling
	 *
	 * @access public
	 * @param mixed $key
	 * @param mixed $path
	 * @return Assets
	 */
	public function css($key, $path = NULL)
	{
		if (is_array($key))
		{
			$this->_paths['css'] = Arr::merge($this->_paths['css'], $key);
		}
		else
		{
			$this->_paths['css'][$key] = $path;
		}
		return $this;
	}
	
	/**
	 * Add js paths or an array of paths for compiling
	 *
	 * @access public
	 * @param mixed $key
	 * @param mixed $path (default: NULL)
	 * @return Assets
	 */
	public function js($key, $path = NULL)
	{
		if (is_array($key))
		{
			$this->_paths['js'] = Arr::merge($this->_paths['js'], $key);
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
	 * @return Assets
	 */
	public function name($name = NULL)
	{
		if ( ! empty($name))
		{
			$this->_name = (string) $name;
			return $this;
		}
		return $this->_name;
	}
	
	/**
	 * Remove a specific css or js path or array of paths from added paths
	 *
	 * @access public
	 * @param mixed $type
	 * @param mixed $key
	 * @return Assets
	 */
	public function remove($type, $key = NULL)
	{
		if (is_array($type))
		{
			foreach ($type as $key => $value)
			{
				unset($this->_paths[$key][$value]);
			}
		}
		else
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
		return $lessc->parse($contents);
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
		return JSMin::minify($contents);
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
		return $this->_compile_dirs[$type].$hash.'.'.$type;
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
		// TODO: replace on preg_match
		if (strpos($path, '/') === 0 OR strpos($path, 'http') === 0 OR strpos($path, ':\\') === 1)
		{
			return $path;
		}
		elseif ($this->_config['pre_compile_dirs'][$type] instanceof Closure)
		{
			return $this->_config['pre_compile_dirs'][$type]($path);
		}
		return $this->_config['pre_compile_dirs'][$type].$path;
	}
	
	/**
	 * Retrieve a namespace from the compiled file
	 * This namespace is the prefix before the '_' in the compiled file name:
	 * ex: 627a74de1fc5a2e750e85a2995a5ac6a332e5018_70e79492f9153932dea60473.js
	 *
	 * @access protected
	 * @param mixed $type
	 * @return string
	 */
	protected function _get_file_namespace($type)
	{
		return md5($this->_name.implode($this->_paths[$type]));
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
		return str_replace(DOCROOT, '', $this->_compile_dirs[$type].$hash.'.'.$type);
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
			return HTML::style($link).PHP_EOL;
		}
		elseif ($type == 'js')
		{
			return HTML::script($link).PHP_EOL;
		}
	}
	
	/**
	 * Make a hash that is the represents a namespace for 
	 * all the assets included in the compiled file
	 * and a last edited date of those files.
	 * The format for this hash is {namespace}_{edited}.ext
	 * ex: 627a74de1fc5a2e750e85a2995a5ac6a332e5018_70e79492f915393.js
	 *
	 * @access protected
	 * @param mixed $type
	 * @return string
	 */
	protected function _make_hash($type)
	{
		$str = '';
		foreach ($this->_paths[$type] as $path)
		{
			$str .= filemtime($this->_get_file_location($type, $path));
		}
		if ( ! empty($str))
		{
			return $this->_get_file_namespace($type).'_'.md5($str);
		}
	}
	
	/**
	 * Remove files from he compiled directory for the same set of assets 
	 * that are outdated do to edits
	 *
	 * @access protected
	 * @param mixed $type
	 * @param mixed $hash
	 * @return void
	 */
	protected function _remove_files($type, $hash)
	{
		$base = $this->_compile_dirs[$type];
		$files = glob($base.$this->_get_file_namespace($type).'_*.'.$type);
		foreach ($files as $file)
		{
			@unlink($file);
		}
		return $this;
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
		file_put_contents($path, $contents, LOCK_EX);
		return $this;
	}
	
} // End Assets
