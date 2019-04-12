<?php
require "combine.php";
require "vendor/autoload.php";

function doStuff() {
	$lua = serializeToLua( getAllExtJson() );
	$lua = UtfNormal\Validator::cleanUp( $lua );
	$password = trim( file_get_contents( '../.mwpass' ) );
//echo $lua; die();
	$wiki = new Wikimate( 'https://mediawiki.org/w/api.php' );

	//$wiki->setDebugMode( true );
	$wiki->login( 'Bawolff_bot', $password );

	$page = $wiki->getPage( 'Module:ExtensionJson' );
	$page->setText( $lua, null, false, 'Resyncing with extension.json from git' );
}

doStuff();
