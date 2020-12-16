<?php

// Copy this to config.php and set the fields.

return [
	// list of extension directories
	// (e.g. clone of https://gerrit.wikimedia.org/r/mediawiki/extensions)
	'extensionDirs' => [ '/path/to/extensions' ],
	// URL of api.php (optional, defaults to the one for mediawiki.org)
	'apiUrl' => null,
	// wiki username of the upload bot
	'username' => 'SomeUser',
	// wiki password of the upload bot
	'password' => 'SomePass',
];
