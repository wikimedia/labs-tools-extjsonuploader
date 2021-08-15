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

	public function run() {
		$collector = new JsonCollector( $this->extensionDirs );
		$luaSerializer = new LuaSerializer();
		$jsonSerializer = new JsonSerializer();
		$collector->setLogger( $this->logger );
		$luaSerializer->setLogger( $this->logger );
		$jsonSerializer->setLogger( $this->logger );

		$data = $collector->collect();

		// Save JSON to public_html.
		$jsonSerializer->serialize( $data, __DIR__ . '/../public_html/ExtensionJson.json' );

		// Create Lua.
		$lua = $luaSerializer->serialize( $data );
		$lua = Validator::cleanUp( $lua );

		$wiki = new Wikimate( $this->apiUrl );
		$res = $wiki->login( $this->wikiUser, $this->wikiPass );
		if ( !$res ) {
			$this->logger->error( 'Could not log in' );
			die();
		}

		$page = $wiki->getPage( 'Module:ExtensionJson' );
		$saved = $page->setText( $lua, null, false, 'Resyncing with extension.json from git' );
		if ( !$saved ) {
			$this->logger->error( 'Error when saving: ' . $page->getError()['info'] );
		}
	}

}
