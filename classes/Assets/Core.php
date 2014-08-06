<?php defined('SYSPATH') or die('No direct script access.');

class Assets_Core {

	/**
	 * Where to save compiled css and js files
	 *
	 * (default value: [])
	 *
	 * @var array
	 * @access protected
	 */
	protected $_compile_paths = [];

	/**
	 * The array from assets config file
	 *
	 * (default value: [])
	 *
	 * @var array
	 * @access protected
	 */
	protected $_config = [];

	/**
	 * Default names for hashgroups
	 * 
	 * @var array
	 * @access protected
	 */
	protected $_default_hashgroup = [
		'css' => 'default',
		'js'  => 'default',
	];

	/**
	 * Whether or not to run a full recompile
	 * 
	 * @var boolean
	 * @access protected
	 */
	protected $_force_recompile = false;

	/**
	 * Stored hash values that point to compiled files
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $_hashes = [
		'css' => [],
		'js' => [],
	];

	/**
	 * All the different hashgroups
	 * 
	 * (default value: [])
	 * 
	 * @var array
	 * @access protected
	 */
	protected $_hashgroup = [];

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
	 * (default value: [])
	 *
	 * @var array
	 * @access protected
	 */
	protected $_import_dirs = [];

	/**
	 * Arrays containing all the added paths for js and css/less
	 * that will be compiled together
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $_paths = array
	(
		'css' => [],
		'js'  => [],
	);


	/**
	 * Factory method returns new assets object
	 *
	 * @access public
	 * @static
	 * @param mixed $name (default: null)
	 * @return Assets_Core
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
	 * @return void
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
	 * @return Assets_Core
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
	public function get($type, $return_only_hash = false)
	{
		$hash = $this->_make_hash($type);

		if ( ! $hash)
		{
			return;
		}

		$compile_path = $this->_get_compile_path($type, $hash);

		if ($this->_force_recompile !== true)
		{	
			if (file_exists($compile_path))
			{
				return $return_only_hash === true
					? $hash
					: $this->_get_tag($type, $hash);
			}
		}

		if ($type === 'css')
		{
			$compiled_contents = $this->_compile_css();
		}
		else
		{
			$contents = '';
			foreach ($this->_paths[$type] as $key => $value)
			{
				$file_location = $this->_get_file_location($type, $value);
	
				$contents.= file_get_contents($file_location);
			}
	
			$compiled_contents = $this->{'_compile_'.$type}($contents);
		}

		$this->_remove_files($type, $hash);
		$this->_write_file($compile_path, $compiled_contents);

		if ($return_only_hash === true)
		{
			return $hash;
		}
		else
		{
			return $this->_get_tag($type, $hash);
		}
	}

	/**
	 * Get all tags.
	 * 
	 * @access public
	 * @param  mixed $type
	 * @return string
	 */
	public function get_tags($type)
	{
		$this->_get_vals();
		$default_name = $this->_default_hashgroup[$type];
		$name = Arr::get($this->_hashgroup, $type) ?: $default_name;

		return $this->get_cdn_tags($type, $name).$this->get_local_tags($type, $default_name, $name);
	}

	/**
	 * Get local assets (cached)
	 * 
	 * @access private
	 * @param  mixed $type
	 * @return string
	 */
	private function get_local_tags($type, $default_name, $name)
	{
		$hash = Arr::path($this->_hashes, $type.'.'.$name) ?: Arr::path($this->_hashes, $type.'.'.$default_name);
		return $this->_get_tag($type, $hash);
	}

	/**
	 * Get CDN assets
	 * 
	 * @access private
	 * @param  mixed $type
	 * @return string
	 */
	private function get_cdn_tags($type, $name)
	{
		$tags = '';
		$config = Kohana::$config->load($type);
		$protocol = Request::current()->secure() ? 'https://' : 'http://';
		
		foreach ($config[$name]['cdn'] as $defaultObject => $links)
		{
			$nbLinks = count($links)-1;
			foreach ($links as $i => $link)
			{
				$wholeLink = $protocol.$link;
				if ($type == 'js')
				{
					if ($i == 0 && $nbLinks > 0)
						$tags .= HTML::script($wholeLink)."\n";
					elseif ($i == 0 && $nbLinks == 0)
						$tags .= HTML::script($link)."\n";
					else
						$tags .= '<script type="text/javascript">'.$defaultObject.' || document.write(\'<script src="'.(($i == $nbLinks) ? URL::site($link) : $wholeLink).'">\x3C/script>\')</script>'."\n";
				}
				else //css
				{
					$tags .= ($i) ?: HTML::style($link)."\n"; //CSS fallback not implemeted yet
				}
			}
		}

		return $tags;
	}

	/**
	 * Set whether or not to force a full recompile
	 * 
	 * @access public
	 * @param mixed $type
	 * @param bool $get_tag (default: false)
	 * @return string
	 */
	public function force_recompile($setting = true)
	{
		$this->_force_recompile = $setting;

		return $this;
	}

	/**
	 * Add import directories for LESS compiling
	 *
	 * @access public
	 * @param array $directories
	 * @return Assets_Core
	 */
	public function import_dirs( array $directories)
	{
		$this->_import_dirs = Arr::merge($this->_import_dirs, $directories);

		return $this;
	}


	/**
	 * Add js paths or an array of paths for compiling
	 *
	 * @access public
	 * @param mixed $key
	 * @param mixed $path (default: NULL)
	 * @return Assets_Core
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
	 * @return Assets_Core
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
	 * @return Assets_Core
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
	 * Set the hashgroup
	 * 
	 * @access public
	 * @param mixed $type
	 * @param mixed $hashgroup
	 * @return void
	 */
	public function set_hashgroup($type, $hashgroup)
	{
		$this->_hashgroup[$type] = $hashgroup;
	}

	/**
	 * Update config file that points to various hashes
	 * 
	 * @access public
	 * @return void
	 */
	public function update_config()
	{
		$css_array = $this->_run_css();
		$js_array = $this->_run_js();

		$array = array
		(
			'css' => $css_array,
			'js' => $js_array,
		);

		$config_contents = "<?php defined('SYSPATH') or die('No direct script access.');\n\n return ";
		$config_contents.= var_export($array, true);
		$config_contents.= ';';

		$this->_hashes = $array;

		$filename = Arr::get($this->_config, 'compile_config_filename', 'asset_defs');
		$pathname = APPPATH.'config/'.$filename.'.php';

		if ( ! file_exists($pathname))
		{
			$fp = fopen($pathname, 'w');
			fwrite($fp, $config_contents);
			fclose($fp);
		}
		else
		{
			file_put_contents($pathname, $config_contents);
		}
	}

	/**
	 * Compile into a single string using PHPLess
	 *
	 * @access protected
	 * @param mixed $contents
	 * @return string
	 */
	protected function _compile_css()
	{
		$parser = new Less_Parser;

		if (Arr::get($this->_config, 'minify_css', true))
		{
			$parser->setOption('compress', true);
		}

		if ( ! empty($this->_import_dirs))
		{
			$parser->SetImportDirs($this->_import_dirs);
		}

		foreach ($this->_paths['css'] as $file)
		{
			$file_location = $this->_get_file_location('css', $file);
			$parser->parseFile($file_location);
		}

		$css = $parser->getCss();
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
		if (Arr::get($this->_config, 'minify_js', true))
		{
			$js = JSMin::minify($contents);
		}
		else
		{
			$js = $contents;
		}

		return $js;
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
		if (strpos($path, '/') === 0 OR strpos($path, 'http') === 0 OR strpos($path,':\\') === 1)
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

		$name = sha1($this->_name.$str);

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
	 * @param boolean $local
	 * @return string
	 */
	protected function _get_tag($type, $hash)
	{
		$link = $this->_get_link_path($type, $hash);
		
		return ($type == 'css')
			? HTML::style($link)."\n"
			: HTML::script($link)."\n"; //js
	}

	/**
	 * Get values from config file.
	 * 
	 * @access protected
	 * @return void
	 */
	protected function _get_vals()
	{
		$filename = Arr::get($this->_config, 'compile_config_filename', 'asset_defs');
		if (empty($this->_hashes['css']))
		{
			$this->_hashes['css'] = Kohana::$config->load($filename.'.css') ?: [];
		}

		if (empty($this->_hashes['js']))
		{
			$this->_hashes['js'] = Kohana::$config->load($filename.'.js') ?: [];
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
	 * Pre-compile all css files
	 * 
	 * @access protected
	 * @return void
	 */
	protected function _run_css()
	{
		$filename = Arr::get($this->_config, 'compile_config_filename', 'asset_defs');

		$config = Kohana::$config->load('css');
		$css_array = [];
		$css_settings = Kohana::$config->load($filename.'.css') ?: [];
		$return_array = $css_settings;

		foreach ($config as $key => $types)
		{
			foreach ($types as $type => $css)
			{
				if ($type == 'local') //Precompile local assets only
				{
					//Add DOCROOT before local CSS files and create new assets object
					$css = array_map(function($css) {
						return DOCROOT.$css;
					}, $css);
					$asset = Assets::factory()
						->css($css)
						->force_recompile($this->_force_recompile);
					if ($hash = $asset->get('css', true))
						$css_array[$key] = $hash;
				}
			}
		}

		if ( ! empty($css_array))
		{
			if ($css_settings !== $css_array)
			{
				$return_array = $css_array;

				// Cleanup
				$files = glob(DOCROOT.$this->_compile_paths['css'].'*');
				foreach ($files as $file)
				{
					preg_match('/\/([a-zA-Z0-9_]+).css$/', $file, $matches);
					if ($match = Arr::get($matches, 1))
					{
						if ( ! in_array($match, $css_array))
						{
							@unlink($file);
						}
					}
				}
			}
		}

		return $return_array;
	}

	/**
	 * Pre-compile js files
	 * 
	 * @access protected
	 * @return array
	 */
	protected function _run_js()
	{
		$filename = Arr::get($this->_config, 'compile_config_filename', 'asset_defs');

		$config = Kohana::$config->load('js');
		$js_array = [];
		$js_settings = Kohana::$config->load($filename.'.js') ?: [];
		$js_settings = [];

		foreach ($config as $key => $types)
		{
			foreach ($types as $type => $js)
			{
				if ($type == 'local') //Precompile local assets only
				{
					//Add DOCROOT before local JS files and create new assets object
					$js = array_map(function($js) {
						return DOCROOT.$js;
					}, $js);
					$asset = Assets::factory()
						->js($js)
						->force_recompile($this->_force_recompile);
					if ($hash = $asset->get('js', true))
						$js_array[$key] = $hash;
				}
			}
		}

		if ( ! empty($js_array))
		{
			if ($js_settings !== $js_array)
			{
				$return_array = $js_array;

				// Cleanup
				$files = glob(DOCROOT.$this->_compile_paths['js'].'*');
				foreach ($files as $file)
				{
					preg_match('/\/([a-zA-Z0-9_]+).js$/', $file, $matches);
					if ($match = Arr::get($matches, 1))
					{
						if ( ! in_array($match, $js_array))
						{
							@unlink($file);
						}
					}
				}
			}
		}

		return $return_array;
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