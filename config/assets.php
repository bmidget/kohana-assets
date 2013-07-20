<?php defined('SYSPATH') OR die('No direct script access.');

return array(
	'compile_dirs' => array(
		'css' => DOCROOT.'assets/css/',
		'js'  => DOCROOT.'assets/js/',
	),
	'pre_compile_dirs' => array(
		'css' => APPPATH.'media/css/',
		'js'  => APPPATH.'media/js/',
	),
);