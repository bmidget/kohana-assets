# JS and CSS assets library for Kohana 3.3

This library make simple including and compressing assets (css-less, js) to your Kohana projects.

It supports local assets and CDN assets for JS files.

## Basic example

### Define your assets

#### config/js.php

```
<?php defined('SYSPATH') OR die('No direct access allowed.');

$base = [
	'cdn' => [
		'window.jQuery' => [ //jQuery and possible fallbacks
			'ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js',
			'code.jquery.com/jquery-2.1.1.min.js',
			'ajax.aspnetcdn.com/ajax/jquery/jquery-2.1.1.min.js',
			URL::site('assets/js/framework/jquery-2.1.1.min.js')
		]
	],
	'local' => [
		'class'        => DOCROOT.'assets/js/framework/class.js',
		'bootstrap'    => DOCROOT.'assets/js/framework/bootstrap-2.3.1.min.js',
		'common'       => DOCROOT.'assets/js/core/common/common.js',
		'jqueryCustom' => DOCROOT.'assets/js/core/common/jquery.custom.js'
	]
];

return [
  'base' => $base
];
```

#### config/css.php
```
<?php defined('SYSPATH') OR die('No direct access allowed.');

/* CSS fallbacks are not implemented yet. Max 1 value per cdn array */

//For all non empty templates
$base = [
	'cdn' => [ //CSS fallback not implemeted yet
		'font' => [ //Default font on error
			'https://fonts.googleapis.com/css?family=Open+Sans:400,300'
		]
	],
	'local' => [
		'bootstrap'  => DOCROOT.'assets/css/framework/bootstrap.css',
		'glyphicons' => DOCROOT.'assets/css/framework/glyphicons.css',
		'common'     => DOCROOT.'assets/css/core/common/common.css'
	]
];

return [
  'base' => $base
];
```

### Create an assets object and set hashgroups

```
$assets = Assets::factory();
$assets->set_hashgroup('css', 'base');
$assets->set_hashgroup('js', 'base');

if (Kohana::$environment != Kohana::PRODUCTION)
	$assets->update_config();
```
The "update_config()" method is useful in order to update the compiled assets at every refresh in your devs environnements.

### Render the tags in your views

```
<?=$assets->get_tags('css')?>
<?=$assets->get_tags('js')?>
```

## More complexe example

You may want to go further by having specific assets for each controller/action using a specific template. For instance, i'll show you how I do this for my RESTful user/edit action.

### Define your assets properly

```
<?php defined('SYSPATH') OR die('No direct access allowed.');

/* CSS fallbacks are not implemented yet. Max 1 value per cdn array */

//For all non empty templates
$base = [
	'cdn' => [ //CSS fallback not implemeted yet
		'font' => [ //Default font on error
			'https://fonts.googleapis.com/css?family=Open+Sans:400,300'
		]
	],
	'local' => [
		'bootstrap'  => DOCROOT.'assets/css/framework/bootstrap.css',
		'glyphicons' => DOCROOT.'assets/css/framework/glyphicons.css',
		'common'     => DOCROOT.'assets/css/core/common/common.css'
	]
];

//Default template specific
$default = [
	'cdn' => [],
	'local' => [
		'antiscroll' 		=> DOCROOT.'assets/css/plugins/jquery.antiscroll.css',
		'fileupload' 		=> DOCROOT.'assets/css/plugins/jquery.fileupload-ui.css',
		'fileuploadeOverride' 	=> DOCROOT.'assets/css/plugins/jquery.fileupload-ui.override.css',
		'qtip'                	=> DOCROOT.'assets/css/plugins/jquery.qtip.min.css',
		'default'    		=> DOCROOT.'assets/css/core/template/default.css'
	]
];

//Controllers and actions
$user = [
	'edit' => [
		'cdn' => [],
		'local' => [
			'edit'  => DOCROOT.'assets/css/core/user/edit.css',
			'jcrop' => DOCROOT.'assets/css/plugins/jquery.jcrop.min.css',
			'jcropOverride' => DOCROOT.'assets/css/plugins/jquery.jcrop.min.override.css'
		]
	]
];

$default = array_merge_recursive($base, $default);
return [
  'front'   => $front,
	'default' => $default,
 	'default/user/edit' => array_merge_recursive($default, $user['edit'])
];
```

### Create an assets object and set hashgroups

```
$assets = Assets::factory();
$group = 'default';
if ($this->hasSpecificAssets)
	$group .= strtolower('/'.Request::current()->controller().'/'.Request::current()->action());
$assets->set_hashgroup('css', $group);
$assets->set_hashgroup('js', $group);
...
```
My custom attribute "hasSpecificAssets" is previously defined in my controllers.

### Finally, render the tags in your views (same way)

```
<?=$assets->get_tags('css')?>
<?=$assets->get_tags('js')?>
```

## Pre-compile assets

You can create a minion task to do this as a deployment script for your production environment.

```
class Task_Asset_Compile extends Minion_Task {

	protected function _execute(array $params)
	{
		$asset = Assets::factory();
		$asset->update_config();
	}

}
```

##That's pretty much it! Enjoy!
