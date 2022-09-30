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

	private const RESERVED = [
		'and', 'break', 'do', 'else', 'elseif',
		'end', 'false', 'for', 'function', 'if',
		'in', 'local', 'nil', 'not', 'or',
		'repeat', 'return', 'then', 'true', 'until', 'while'
	];

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
	 * Convert to an unquoted name if possible, otherwise do normal string.
	 *
	 * @param string $stuff
	 * @return bool
	 */
	private function convertToLuaIdentifier( $stuff ) {
		if (
			is_string( $stuff ) &&
			preg_match( "/^[a-zA-Z][a-zA-Z0-9_]*$/", $stuff ) &&
			!in_array( $stuff, self::RESERVED )
		) {
			return $stuff;
		} else {
			return '[' . $this->convertToLua( $stuff ) . ']';
		}
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
			// Bit hacky, try and figure out if it is numeric array.
			if ( isset( $stuff[0] ) && isset( $stuff[count( $stuff ) - 1] ) ) {
				foreach ( $stuff as $value ) {
					$out .= $this->convertToLua( $value ) . ',';
				}
			} else {
				foreach ( $stuff as $key => $value ) {
					// $out .= str_repeat( "\t", $level );
					if ( is_int( $key ) ) {
						// lua is 1-based.
						$key++;
					}
					$out .= $this->convertToLuaIdentifier( $key ) . '='
						. $this->convertToLua( $value, $level + 1 ) . ",\n";
				}
			}
			// We are running out of space, don't pretty print.
			// $out .= str_repeat( "\t", $level - 1 );
			$out .= "}";
			return $out;
		}
		$this->logger->error( "$stuff is invalid type" );
		die();
	}

}
