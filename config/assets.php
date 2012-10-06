<?php defined('SYSPATH') or die('No direct script access.');

return array
(
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