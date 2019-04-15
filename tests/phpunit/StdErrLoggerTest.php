<?php

namespace MediaWiki\Tools\ExtensionJsonUploader;

use PHPUnit\Framework\TestCase;
use Wikimedia\TestingAccessWrapper;

class StdErrLoggerTest extends TestCase {

	/**
	 * @covers \MediaWiki\Tools\ExtensionJsonUploader\StdErrLogger::log
	 */
	public function testLog() {
		$logger = new StdErrLogger();
		$loggerWrapper = TestingAccessWrapper::newFromObject( $logger );
		$loggerWrapper->fh = fopen( 'php://memory', 'rw' );

		$logger->error( 'This is fine.' );

		fseek( $loggerWrapper->fh, 0 );
		$this->assertSame( 'This is fine.' . PHP_EOL, stream_get_contents( $loggerWrapper->fh ) );
	}

}
