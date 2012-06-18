<?php defined('SYSPATH') or die('No direct script access.');

require Kohana::find_file('vendor', 'lessphp/lessc.inc');
require Kohana::find_file('vendor', 'jsmin/jsmin');

class Assets_Core {

	protected $_css = array();
	protected $_compile_paths = array
	(
		'css' => 'assets/css/compiled',
		'js'  => 'assets/js/compiled',
	);
	protected $_js = array();
	protected $_name = 'core';

	public static function factory($name = null)
	{
		return new Assets($name);
	}

	public function __construct($name = null)
	{
		if ($name !== null)
		{
			$this->_name = $name;
		}
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
			$this->_css[$key] = $path;
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
		foreach ($this->{'_'.$type} as $key => $value)
		{
			$contents.= file_get_contents(DOCROOT.'assets/'.$type.'/'.$value);
		}

		$compiled_contents = $this->{'_compile_'.$type}($contents);
		$this->_write_file($compile_path, $compiled_contents);

		return $this->_get_tag($type, $hash);
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
			$this->_js[$key] = $path;
		}

		return $this;
	}

	public function remove($type, $key)
	{
		if (isset($this->{'_'.$type}[$key]))
		{
			unset($this->{'_'.$type}[$key]);
		}
	}

	protected function _compile_css($contents)
	{
		$lessc = new lessc;
		$css = $lessc->parse($contents);

		return $css;
	}

	protected function _compile_js($contents)
	{
		$mini_js = JSMin::minify($contents);
		return $mini_js;
	}

	protected function _get_compile_path($type, $hash)
	{
		$compile_path = DOCROOT.$this->_compile_paths[$type].'/'.$hash.'.'.$type;

		return $compile_path;
	}

	protected function _get_link_path($type, $hash)
	{
		return $this->_compile_paths[$type].'/'.$hash.'.'.$type;
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
		$array = $this->{'_'.$type};

		$str = '';
		foreach ($array as $path)
		{
			$str.= filemtime(DOCROOT.'assets/'.$type.'/'.$path);
		}

		$hash = $str
			? $this->_name.'_'.sha1($str)
			: NULL;

		return $hash;
	}

	protected function _write_file($path, $contents)
	{
		file_put_contents($path, $contents);
	}

}