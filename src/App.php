<?php

namespace MediaWiki\Tools\ExtensionJsonUploader;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use UtfNormal\Validator;
use Wikimate;

/**
 * Main application
 */
class App implements LoggerAwareInterface {

	use LoggerAwareTrait;

	/** @var string[] Directory where the mediawiki/extensions repo is checked out */
	private $extensionDirs;

	/** @var string MediaWiki API URL */
	private $apiUrl = 'https://www.mediawiki.org/w/api.php';

	/** @var string Bot username */
	private $wikiUser;

	/** @var string Bot password */
	private $wikiPass;

	/**
	 * @param string[] $extensionDirs
	 * @param string $wikiUser
	 * @param string $wikiPass
	 */
	public function __construct( array $extensionDirs, $wikiUser, $wikiPass ) {
		$this->extensionDirs = $extensionDirs;
		$this->wikiUser = $wikiUser;
		$this->wikiPass = $wikiPass;
		$this->logger = new NullLogger();
	}

	/**
	 * @param string $url
	 */
	public function setApiUrl( $url ) {
		$this->apiUrl = $url;
	}

	/**
	 * Collect extension data.
	 * @return array[] JSON-able data array, keyed by extension name
	 */
	public function collect() {
		$collector = new JsonCollector( $this->extensionDirs );
		$collector->setLogger( $this->logger );
		return $collector->collect();
	}

	public function run() {
		$luaSerializer = new LuaSerializer();
		$jsonSerializer = new JsonSerializer();
		$luaSerializer->setLogger( $this->logger );
		$jsonSerializer->setLogger( $this->logger );

		$data = $this->collect();

		// Save JSON to public_html.
		$jsonSerializer->serialize( $data, __DIR__ . '/../public_html/ExtensionJson.json' );

		// Create Lua.
		$lua = $luaSerializer->serialize( $data );
		$lua = Validator::cleanUp( $lua );

		file_put_contents( __DIR__ . '/../public_html/extension.lua', $lua );

		$wiki = new Wikimate( $this->apiUrl, [], [], [ 'timeout' => 30 ] );
		$wiki->setUserAgent( 'toolforge/extjsonuploader' );
		$res = $wiki->login( $this->wikiUser, $this->wikiPass );
		if ( !$res ) {
			$this->logger->error( 'Could not log in' );
			exit( 1 );
		}

		$page = $wiki->getPage( 'Module:ExtensionJson' );
		$saved = $page->setText( $lua, null, false, 'Resyncing with extension.json from git' );
		if ( !$saved ) {
			$this->logger->error( 'Error when saving: ' . $page->getError()['info'] );
			exit( 1 );
		}
	}

}
