<?php

namespace MediaWiki\Tools\ExtensionJsonUploader;

use Psr\Log\AbstractLogger;

/**
 * Simple PSR-3 logger that uses stderr.
 * Ignores log levels and context but we only log error messages anyway.
 */
class StdErrLogger extends AbstractLogger {

	/** @var resource Standard error handle */
	private $fh;

	public function log( $level, $message, array $context = [] ) {
		if ( !$this->fh ) {
			$this->open();
		}
		fwrite( $this->fh, $message ."\n" );
	}

	private function open() {
		$this->fh = fopen( 'php://stderr', 'w' );
		if ( !$this->fh ) {
			die( 'error opening stderr' );
		}
	}

}
