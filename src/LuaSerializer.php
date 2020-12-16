<?php

namespace MediaWiki\Tools\ExtensionJsonUploader;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Converts PHP data into a Lua (Scribunto) module
 */
class LuaSerializer implements LoggerAwareInterface {

	use LoggerAwareTrait;

	public function __construct() {
		$this->logger = new NullLogger();
	}

	/**
	 * Convert JSON data into a Lua module. The return from the call is suitable
	 * for writing to a Module: namespace page and loading with mw.loadData.
	 * @param array $stuff
	 * @return string
	 */
	public function serialize( array $stuff ) {
		return 'return ' . $this->convertToLua( $stuff );
	}

	/**
	 * Convert JSON data into a Lua table.
	 * @param array|string|int|float|bool|null $stuff
	 * @param int $level Indentation level
	 * @return string
	 */
	protected function convertToLua( $stuff, $level = 1 ) {
		if ( is_string( $stuff ) ) {
			return '"' . addcslashes( $stuff, "\0..\37\"\\" ) . '"';
		}

		if ( is_int( $stuff ) || is_float( $stuff ) ) {
			return (string)$stuff;
		}
		if ( is_bool( $stuff ) ) {
			return $stuff ? 'true' : 'false';
		}
		if ( $stuff === null ) {
			return 'nil';
		}

		if ( is_array( $stuff ) ) {
			$out = "{\n";
			foreach ( $stuff as $key => $value ) {
				$out .= str_repeat( "\t", $level );
				$out .= '[' . $this->convertToLua( $key ) . '] = ' . $this->convertToLua( $value, $level + 1 ) . ",\n";
			}
			$out .= str_repeat( "\t", $level - 1 ) . "}";
			return $out;
		}
		$this->logger->error( "$stuff is invalid type" );
		die();
	}

}
