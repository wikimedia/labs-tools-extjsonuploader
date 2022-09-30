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
		$this->assertSame( "return {\na=\"b\",\n}", $serializer->serialize( [ 'a' => 'b' ] ) );
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
			[ [ 'a' => 'b' ], "{\na=\"b\",\n}" ],
			[ [ 'a' => 'b', 'c' => [ 'd' => 0 ] ], "{\na=\"b\",\nc={\nd=0,\n},\n}" ],
			[ [ 'if' => 1 ], "{\n[\"if\"]=1,\n}" ]
		];
	}

}
