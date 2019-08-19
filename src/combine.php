<?php

function getAllExtJson() {
	$files = glob( '/data/project/extjsonuploader/allext/extensions/*/extension.json' );

	$overall = [];
	foreach( $files as $file ) {
		$filetext = file_get_contents( $file );
		$ext = json_decode( $filetext, true /*assoc*/);
		if ( !$ext ) {
			continue;
		}

		// Keep size down. 2mb limit in mediawiki
		unset( $ext[ 'ResourceModules' ] );
		unset( $ext[ 'AutoloadClasses' ] );

		$name = getName( $ext );
		if ( !$name ) {
			debug( "$file has no name" );
			continue;
		}
		if ( isset( $overall[$name] ) ) {
			debug( "$file has duplicate name $name" );
		}
		$overall[$name] = $ext;
	}
	return $overall;
}

/**
 * Name to include under. We want to match up with MW pages,
 * so prefer url of extension page over actual name.
 */
function getName( array $json ) {
	$m = [];
	if ( isset( $json['url'] ) && preg_match( '!^https?://www.mediawiki.org/wiki/Extension:([^?#]*)$!i', $json['url'], $m ) ) {
		return urldecode( $m[1] );
	}
	if ( isset( $json['name'] ) && is_string( $json['name'] ) ) {
		return $json['name'];
	}
	debug( "No name detected" );
	return false;
}

function debug( $msg ) {
	static $fh;
	if ( !$fh ) {
		$fh = fopen( "php://stderr", 'w' );
		if ( !$fh ) die( "error opening stderr" );
	}

	fwrite( $fh, $msg ."\n" );
}

function serializeToLua ( array $stuff ) {
	return 'return ' . convertToLua( $stuff );
}

function convertToLua( $stuff, $level = 1 ) {
	if ( is_string( $stuff ) ) {
		return '"' . addcslashes( $stuff, "\0..\37\"\\" ) . '"';
	}

	if ( is_int( $stuff ) || is_float( $stuff ) ) {
		return $stuff;
	}
	if ( is_bool( $stuff ) ) {
		return $stuff ? 'true' : 'false';
	}
	if ( is_null( $stuff ) ) {
		return 'nil';
	}

	if ( is_array( $stuff ) ) {
		$out = "{\n";
		foreach( $stuff as $key => $value ) {
			$out .= str_repeat( "\t", $level );
			$out .= '[' . convertToLua( $key ) . '] = ' . convertToLua( $value, $level + 1 ) . ",\n";
		}
		$out .= str_repeat( "\t", $level - 1 ) . "}";
		return $out;
	}
var_dump( $stuff );
	debug( "$stuff is invalid type" );
	die();
}



