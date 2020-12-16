<?php

namespace MediaWiki\Tools\ExtensionJsonUploader;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Collect all extension.json data into an array.
 */
class JsonCollector implements LoggerAwareInterface {

	use LoggerAwareTrait;

	/** @var string */
	protected $extensionsPath;

	/**
	 * @param string $extensionsPath Path to extensions directory (e.g. checkout of
	 *   git clone https://gerrit.wikimedia.org/r/mediawiki/extensions )
	 */
	public function __construct( $extensionsPath ) {
		$this->extensionsPath = rtrim( $extensionsPath, '/' );
		$this->logger = new NullLogger();
	}

	/**
	 * Returns all extension.json data, in an associative array keyed by
	 * extension name (which tries to match the wiki name if possible).
	 * @return array
	 */
	public function collect() {
		$files = glob( $this->extensionsPath . '/*/extension.json' );

		$overall = [];
		foreach ( $files as $file ) {
			$filetext = file_get_contents( $file );
			$ext = json_decode( $filetext, true );
			if ( !$ext ) {
				continue;
			}

			$name = $this->getName( $ext );
			if ( !$name ) {
				$this->logger->error( "$file has no name" );
				continue;
			}
			if ( isset( $overall[$name] ) ) {
				$this->logger->error( "$file has duplicate name $name" );
			}
			$overall[$name] = $ext;
		}
		return $overall;
	}

	/**
	 * Name to include under. We want to match up with MW pages,
	 * so prefer url of extension page over actual name.
	 * @param array|false $json Extension data
	 * @return string|false
	 */
	protected function getName( array $json ) {
		$m = [];
		$pattern = '!^https?://www.mediawiki.org/wiki/Extension:([^?#]*)$!i';
		if ( isset( $json['url'] ) && preg_match( $pattern, $json['url'], $m ) ) {
			return urldecode( $m[1] );
		} elseif ( isset( $json['name'] ) && is_string( $json['name'] ) ) {
			return $json['name'];
		}
		return false;
	}

}
