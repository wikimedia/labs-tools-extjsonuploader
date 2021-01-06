<?php

namespace MediaWiki\Tools\ExtensionJsonUploader;

use PHPUnit\Framework\TestCase;

class JsonCollectorTest extends TestCase {

	/**
	 * @covers \MediaWiki\Tools\ExtensionJsonUploader\JsonCollector::collect
	 */
	public function testCollect() {
		$collector = new JsonCollector( [ __DIR__ . '/../fixtures/extensions' ] );
		$data = $collector->collect();
		$this->assertIsArray( $data );
		$this->assertCount( 2, $data );
		$this->assertSame( 'Buggy', $data['Buggy']['name'] );
	}

}
