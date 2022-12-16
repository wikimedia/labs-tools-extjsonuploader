<?php

namespace MediaWiki\Tools\ExtensionJsonUploader;

use PHPUnit\Framework\TestCase;
use Wikimedia\TestingAccessWrapper;
use Wikimedia\Timestamp\ConvertibleTimestamp;

class StdErrLoggerTest extends TestCase {

	/**
	 * @covers \MediaWiki\Tools\ExtensionJsonUploader\StdErrLogger::log
	 */
	public function testLog() {
		ConvertibleTimestamp::setFakeTime( '2022-12-11 00:48:20' );

		$logger = new StdErrLogger();
		$loggerWrapper = TestingAccessWrapper::newFromObject( $logger );
		$loggerWrapper->fh = fopen( 'php://memory', 'rw' );

		$logger->error( 'This is fine.' );

		fseek( $loggerWrapper->fh, 0 );
		$this->assertSame( "[2022-12-11 00:48:20+00] This is fine.\n", stream_get_contents( $loggerWrapper->fh ) );
	}

}
