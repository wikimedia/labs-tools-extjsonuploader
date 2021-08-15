<?php

namespace MediaWiki\Tools\ExtensionJsonUploader;

use PHPUnit\Framework\TestCase;

class JsonSerializerTest extends TestCase {

	/**
	 * @covers \MediaWiki\Tools\ExtensionJsonUploader\JsonSerializer
	 */
	public function testSerialize() {
		$data = [ 'foo' => 'bar' ];
		$serializer = new JsonSerializer();
		$filename = __DIR__ . '/test.json';
		$serializer->serialize( $data, $filename );
		$this->assertJsonStringEqualsJsonFile( $filename, '{ "foo": "bar" }' );
	}

}
