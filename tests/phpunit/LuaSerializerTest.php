<?php

namespace MediaWiki\Tools\ExtensionJsonUploader;

use PHPUnit\Framework\TestCase;
use Wikimedia\TestingAccessWrapper;

class LuaSerializerTest extends TestCase {

	/**
	 * @covers \MediaWiki\Tools\ExtensionJsonUploader\LuaSerializer::serialize
	 */
	public function testSerialize() {
		$serializer = new LuaSerializer();
		$this->assertSame( "return {\n\t[\"a\"] = \"b\",\n}", $serializer->serialize( [ 'a' => 'b' ] ) );
	}

	/**
	 * @dataProvider provideConvertToLua
	 * @covers \MediaWiki\Tools\ExtensionJsonUploader\LuaSerializer::convertToLua
	 */
	public function testConvertToLua( $input, $expectedOutput ) {
		$serializer = TestingAccessWrapper::newFromObject( new LuaSerializer() );
		$this->assertSame( $expectedOutput, $serializer->convertToLua( $input ) );
	}

	public function provideConvertToLua() {
		return [
			[ 'abc', '"abc"' ],
			[ 'a "b" "c\\"d"', '"a \"b\" \"c\\\\\\"d\""' ],
			[ 1, '1', ],
			[ 1.0, '1' ],
			[ 1.1, '1.1' ],
			[ true, 'true' ],
			[ false, 'false' ],
			[ null, 'nil' ],
			[ [ 'a' => 'b' ], "{\n\t[\"a\"] = \"b\",\n}" ],
			[ [ 'a' => 'b', 'c' => [ 'd' => 0 ] ], "{\n\t[\"a\"] = \"b\",\n\t[\"c\"] = {\n\t\t[\"d\"] = 0,\n\t},\n}" ],
		];
	}

}
