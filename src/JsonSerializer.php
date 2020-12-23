<?php

namespace MediaWiki\Tools\ExtensionJsonUploader;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Converts PHP data into JSON saved in a file.
 */
class JsonSerializer implements LoggerAwareInterface {

	use LoggerAwareTrait;

	public function __construct() {
		$this->logger = new NullLogger();
	}

	/**
	 * @param array $stuff
	 * @param string $filename
	 */
	public function serialize( array $stuff, string $filename ) {
		$this->logger->debug( 'Writing JSON to ' . $filename );
		file_put_contents( $filename, json_encode( $stuff ) );
	}
}
