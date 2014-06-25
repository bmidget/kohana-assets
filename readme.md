# JS and CSS assets libaray for Kohana 3.3

## Usage example

### Define your assets

#### config/js.php

```
<?php defined('SYSPATH') OR die('No direct access allowed.');

$base = [
	'cdn' => [
		'window.jQuery' => [ //jQuery and possible fallbacks
			'http://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js',
			'http://code.jquery.com/jquery-2.1.1.min.js',
			'http://ajax.aspnetcdn.com/ajax/jquery/jquery-2.1.1.min.js',
			'assets/js/framework/jquery-2.1.1.min.js'
		]
	],
	'local' => [
		'class'        => DOCROOT.'assets/js/framework/class.js',
		'bootstrap'    => DOCROOT.'assets/js/framework/bootstrap-2.3.1.min.js',
		'common'       => DOCROOT.'assets/js/core/common/common.js',
		'jqueryCustom' => DOCROOT.'assets/js/core/common/jquery.custom.js'
	]
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
```

## Create an assets object and set hashgroups

```
$assets = Assets::factory();
$assets->set_hashgroup('css', 'base');
$assets->set_hashgroup('js', 'base');

if (Kohana::$environment != Kohana::PRODUCTION)
	$assets->update_config();
```
The "update_config()" method is useful in order to update the compiled assets every refresh in your devs environnements.


## Render the tags in your views

```
<?=$assets->get_tags('css')?>
<?=$assets->get_tags('js')?>
```

### Pre-compile assets

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

## And in your view template, return the script tags pointing to the proper compiled js and css files

```
<?=$assets->get_cached('css', true)?>
<?=$assets->get_cached('js', true)?>
```

That's pretty much it! Enjoy!
