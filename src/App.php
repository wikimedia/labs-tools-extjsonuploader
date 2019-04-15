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

	/** @var string Directory where the mediawiki/extensions repo is checked out */
	private $extensionDir;

	/** @var string MediaWiki API URL */
	private $apiUrl = 'https://www.mediawiki.org/w/api.php';

	/** @var string Bot username */
	private $wikiUser;

	/** @var string Bot password */
	private $wikiPass;

	public function __construct( $extensionDir, $wikiUser, $wikiPass ) {
		$this->extensionDir = $extensionDir;
		$this->wikiUser = $wikiUser;
		$this->wikiPass = $wikiPass;
		$this->logger = new NullLogger();
	}

	public function setApiUrl( $url ) {
		$this->apiUrl = $url;
	}

	public function run() {
		$collector = new JsonCollector( $this->extensionDir );
		$serializer = new LuaSerializer();
		$collector->setLogger( $this->logger );
		$serializer->setLogger( $this->logger );

		$data = $collector->collect();
		$lua = $serializer->serialize( $data );
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
