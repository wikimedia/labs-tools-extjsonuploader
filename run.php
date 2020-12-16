#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';

$app = new MediaWiki\Tools\ExtensionJsonUploader\App(
	$config['extensionDir'],
	$config['username'],
	$config['password']
);
$app->setLogger( new MediaWiki\Tools\ExtensionJsonUploader\StdErrLogger() );
if ( $config['apiUrl'] ) {
	$app->setApiUrl( $config['apiUrl'] );
}

$app->run();
