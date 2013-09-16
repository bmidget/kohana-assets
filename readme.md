# JS and CSS assets libaray for Kohana 3.3

## Create an assets object

```
$assets = Assets::factory();
```

## Define hashgroups in config files. Here are some examples:

```
// config/js.php
$base = [
	'bs' => DOCROOT.'assets/bootstrap/dist/js/bootstrap.min.js',
	'site' => DOCROOT.'assets/js/site.js',
];

return [
	'default' => $base,
	'admin' => $base + [
		'footable' => DOCROOT.'assets/footable/js/footable.js',
		'footable-paginate' => DOCROOT.'assets/footable/js/footable.paginate.js',
		'footable-filter' => DOCROOT.'assets/footable/js/footable.filter.js',
		'footable-sort' => DOCROOT.'assets/footable/js/footable.sort.js',
		'admin' => DOCROOT.'assets/js/admin.js',
	]
];

// config/css.php
return [
	'default' = [
		'site' => DOCROOT.'assets/less/site.less',
	],
	'public' => [
		'site' => DOCROOT.'assets/less/site.less',
		'public' => DOCROOT.'assets/less/public.less',
	],
	'admin' => [
		'site' => DOCROOT.'assets/less/site.less',
		'footable' => DOCROOT.'assets/less/footable.less',
		'admin' => DOCROOT.'assets/less/admin.less',
	],
	'special_page' => [
		'site' => DOCROOT.'assets/less/site.less',
		'special' => DOCROOT.'assets/less/special.less',
	],
];
```

## Pre-compile assets

You can create a minion task to do this as a deployment script for your production environment.

```
class Task_Asset_Compile extends Minion_Task {

	protected function _execute( array $params)
	{
		$asset = Assets::factory();
		$asset->update_config();
	}

}
```

And in your dev environment, it's nice to update the compiled assets every refresh. Here's an example of setting this up in a Template controller:

```
$this->template->bind('assets', $this->_assets);

$this->_assets = Assets::factory();

if (Kohana::$environment === Kohana::DEVELOPMENT)
{
	$this->_assets->update_config();
}
```

## And in your view template, return the script tags pointing to the proper compiled js and css files

```
<?=$assets->get_cached('css', true)?>
<?=$assets->get_cached('js', true)?>
```