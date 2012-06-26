# JS and CSS assets library for Kohana

- Compile and serve js, css, and less files
- For Kohana 3.2.x

## Create an assets object

```
$assets = Assets::factory();
```

## Add css or js files to the assets object

Every asset you add has to have a key and a value associated with it. They key is to keep track of all the assets already added, and the value is the path to the asset.

!! If you want to add asset files to your assets object that area located in the directory defined in your config file, do not start your path with a `/`.

```
// With an array
$assets
	->css(array(
		'base' => 'base.less',
		'section' => 'section.less',
	))
	->js(array(
		'plugins' => 'plugins/plugins.js',
		'section' => 'section.js',
	));

// You can also add them one at a time
$assets
	->css('base', 'base.less')
	->js('section', 'section.js');

// Tell Assets to use a specific path for your asset by beginning the value with a `/`
$assets
	->css('bootstrap', DOCROOT.'bootstrap/less/bootstrap.less')
	->js('site' => Kohana::find_file('media', 'js/site.js'));
```

## Render assets

To render assets that have been added to the assets object, use `Assets::get()`.

```
<?=$assets->get('css')?>
<?=$assets->get('js')?>
```