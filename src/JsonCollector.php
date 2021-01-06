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

	/** @var string[] */
	protected $extensionsDirs;

	/**
	 * @param string[] $extensionsDirs List of extensions directories
	 *   (e.g. clone of https://gerrit.wikimedia.org/r/mediawiki/extensions )
	 */
	public function __construct( $extensionsDirs ) {
		$this->extensionsDirs = $extensionsDirs;
		$this->logger = new NullLogger();
	}

	/**
	 * Returns all extension.json data, in an associative array keyed by
	 * extension name (which tries to match the wiki name if possible).
	 * @return array
	 */
	public function collect() {
		$overall = [];
		foreach ( $this->getFiles() as $file ) {
			$filetext = file_get_contents( $file );
			$ext = json_decode( $filetext, true );
			if ( !$ext ) {
				continue;
			}

			// Keep size down under the 2MB limit of mediawiki.org
			unset( $ext['ResourceModules'] );
			unset( $ext['AutoloadClasses'] );

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
	 * Iterate through extension directories and list the files.
	 * @return Generator<string>
	 */
	protected function getFiles() {
		foreach ( $this->extensionsDirs as $dir ) {
			$dir = rtrim( $dir, '/' );
			foreach ( glob( $dir . '/*/extension.json' ) as $file ) {
				yield $file;
			}
		}
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
