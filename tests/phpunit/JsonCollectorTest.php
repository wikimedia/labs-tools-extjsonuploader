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

	/**
	 * Extension data should include a `repository` key with a URL.
	 * @covers \MediaWiki\Tools\ExtensionJsonUploader\JsonCollector::collect
	 * @covers \MediaWiki\Tools\ExtensionJsonUploader\JsonCollector::getRemoteUrl
	 */
	public function testRemoteUrl() {
		$collector = new JsonCollector( [ __DIR__ . '/../fixtures/extensions' ] );
		$data = $collector->collect();
		$this->assertArrayHasKey( 'repository', $data['Buggy'] );
		$this->assertSame( 'https://example.org/Buggy.git', $data['Buggy']['repository'] );
	}
}
