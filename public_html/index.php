<?php
/**
 * List available data files.
 * @file
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>ExtensionJson</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<h1>Extensions' data</h1>
<p>This tool makes all MediaWiki extensions' metadata available in Lua or JSON.</p>
<ul>
	<li>
		Lua module on MediaWiki.org:
		<a href="https://www.mediawiki.org/wiki/Module:ExtensionJson">Module:ExtensionJson</a>
	</li>
	<li>
		Single JSON file:
		<a href="./ExtensionJson.json">ExtensionJson.json</a>
		(<?php echo round( filesize( 'ExtensionJson.json' ) / 1024 / 1024, 2 ) ?> MB)
	</li>
	<li>
		Popularity data: <a href="https://www.mediawiki.org/wiki/Template:Extension/popularity.json">
		Template:Extension/popularity.json</a>.
	</li>
	<li>
		Miscellaneous files: <a href="./ExtensionPopularity.json">ExtensionPopularity.json</a>,
		<a href="./wikiapiary-extensions.json">wikiapiary-extensions.json</a>,
		<a href="./wikiapiary-skins.json">wikiapiary-skins.json</a>, <a href="extension.lua">extension.lua</a>.
	</li>
</ul>
<p>
	For more information,
	see <a href="https://wikitech.wikimedia.org/wiki/Tool:Extjsonuploader">wikitech:Tool:Extjsonuploader</a></p>
</body>
</html>
