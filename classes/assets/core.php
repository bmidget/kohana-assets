<?php defined('SYSPATH') or die('No direct script access.');

require Kohana::find_file('vendor', 'lessphp/lessc.inc');
require Kohana::find_file('vendor', 'jsmin/jsmin');

class Assets_Core {

	protected $_css_bootstrap;
	protected $_compile_paths = array();
	protected $_config = array();
	protected $_name = 'core';
	protected $_import_dirs = array();
	protected $_paths = array
	(
		'css' => array(),
		'js'  => array(),
	);

	public static function factory($name = null)
	{
		return new Assets($name);
	}

	public function __construct($name = null)
	{
		$this->_config = Kohana::$config->load('assets');

		if ($name !== null)
		{
			$this->_name = $name;
		}

		$this->_compile_paths = Arr::get($this->_config, 'compile_paths');
	}

	public function css_bootstrap($path)
	{
		$this->_css_bootstrap = $path;

		return $this;
	}

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

	public function name($name)
	{
		$this->_name = $name;

		return $this;
	}

	public function remove($type, $key)
	{
		if (isset($this->_paths[$type][$key]))
		{
			unset($this->_paths[$type][$key]);
		}
	}

	protected function _compile_css($contents)
	{
		if ($this->_css_bootstrap)
		{
			$lessc = new lessc($this->_get_file_location('css', $this->_css_bootstrap));
			$bootstrap = $lessc->parse();
		}
		else
		{
			$bootstrap = null;
		}

		$lessc = new lessc;
		if ( ! empty($this->_import_dirs))
		{
			$lessc->importDir = $this->_import_dirs;
		}
		$css = $lessc->parse($bootstrap.$contents);

		return $css;
	}

	protected function _compile_js($contents)
	{
		$mini_js = JSMin::minify($contents);
		return $mini_js;
	}

	protected function _get_compile_path($type, $hash)
	{
		$compile_path = DOCROOT.$this->_compile_paths[$type].$hash.'.'.$type;

		return $compile_path;
	}

	protected function _get_file_location($type, $path)
	{
		if (strpos($path, '/') === 0)
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

	protected function _get_file_name($type)
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

	protected function _get_link_path($type, $hash)
	{
		return $this->_compile_paths[$type].$hash.'.'.$type;
	}

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
			? $this->_get_file_name($type).'_'.sha1($str)
			: NULL;

		return $hash;
	}

	protected function _remove_files($type, $hash)
	{
		$base = DOCROOT.$this->_compile_paths[$type];

		$files = glob($base.$this->_get_file_name($type).'_*.'.$type);

		foreach ($files as $file)
		{
			unlink($file);
		}
	}

	protected function _write_file($path, $contents)
	{
		file_put_contents($path, $contents);
	}

}