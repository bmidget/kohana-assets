<?php defined('SYSPATH') or die('No direct script access.');

return array
(
	'minify_js' => true,
	'compile_config_filename' => 'asset_defs',
	'compile_paths' => array
	(
		'css' => 'assets/css/compiled/',
		'js'  => 'assets/js/compiled/',
	),
	'pre_compile_dirs' => array
	(
		'css' => DOCROOT.'assets/css/',
		'js'  => DOCROOT.'assets/js/',
	),
);