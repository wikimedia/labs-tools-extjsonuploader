<?php

namespace MediaWiki\Tools\ExtensionJsonUploader;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Wikimate;

/**
 * Main application
 */
class PopularityApp implements LoggerAwareInterface {

	use LoggerAwareTrait;

	/** @var string MediaWiki API URL */
	private $apiUrl = 'https://www.mediawiki.org/w/api.php';

	/** @var string Bot username */
	private $wikiUser;

	/** @var string Bot password */
	private $wikiPass;

	/**
	 * @param string $wikiUser
	 * @param string $wikiPass
	 */
	public function __construct( $wikiUser, $wikiPass ) {
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
	 * @param string $type "extensions" or "skins"
	 * @param string $period A time period, e.g. "8w"
	 * @return array
	 */
	private function getGraphiteStats( string $type, string $period ): array {
		// We get a timeout if we do it all at once.
		$segments = [ "[0-A]", "[B-C]", "[D-H]", "[I-M]", "[N-Q]", "[R-V]", "[W-Y]", "[Z-z]" ];

		$combinedData = [];
		foreach ( $segments as $seg ) {
			$url = "https://graphite.wikimedia.org/render?" .
				"target=sortByMaxima(groupByNode(summarize(MediaWiki.extdist.$type.$seg*" .
				".*.sum,%22$period%22,%22sum%22,true),3,%22sum%22))&format=json&from=-$period&until=now";

			// Hopefully fopen is enabled.
			$context = stream_context_create( [ 'http' => [ 'user_agent' => 'toolforge/extjsonuploader' ] ] );
			$json = file_get_contents( $url, false, $context );
			if ( !$json ) {
				$this->logger->error( "Could not download $url" );
				exit( 1 );
			}
			$data = json_decode( $json, true );
			if ( !$data || !isset( $data[0]['target'] ) ) {
				$this->logger->error( "Could not parse $url" );
				exit( 1 );
			}
			foreach ( $data as $item ) {
				// Graphite seems to return null isntead of 0.
				$combinedData[$item['target']] = (int)( $item['datapoints'][0][0] );
			}
		}
		arsort( $combinedData );
		return $combinedData;
	}

	private function makeStatsDownloads(): array {
		$stats = [];
		// Meant to be more fair versions of 1 week, 1 month, 1 quarter, 1 year, all time
		$timePeriods = [ '1w', '4w', '13w', '1y', '20y' ];
		foreach ( [ 'extensions', 'skins' ] as $type ) {
			if ( !isset( $stats[$type] ) ) { $stats[$type] = [];
			}
			foreach ( $timePeriods as $timePeriod ) {
				$data = $this->getGraphiteStats( $type, $timePeriod );
				$rank = 0;
				$prevHits = false;
				foreach ( $data as $target => $hits ) {
					if ( $prevHits !== $hits ) {
						// Ensure ties are ranked the same
						$rank++;
						$prevHits = $hits;
					}
					if ( !isset( $stats[$type][$target] ) ) { $stats[$type][$target] = [];
					}
					if ( !isset( $stats[$type][$target]['downloads'] ) ) {
						$stats[$type][$target]['downloads'] = [];
					}
					if ( !isset( $stats[$type][$target]['downloadsRank'] ) ) {
						$stats[$type][$target]['downloadsRank'] = [];
					}
					$stats[$type][$target]['downloads'][$timePeriod] = $hits;
					$stats[$type][$target]['downloadsRank'][$timePeriod] = $rank;
				}
			}
		}
		return $stats;
	}

	/**
	 * Create stats from WikiApiary
	 *
	 * @param array &$stats Stats array to add to
	 */
	private function doWikiapiary( &$stats ) {
		// It seems like max limit is 500. A future todo might be to do offset paging to get more.
		$urlExt = 'https://wikiapiary.com/wiki/Special:Ask/format%3Djson/link%3Dall/' .
			'sort%3DHas-5Fwebsite-5Fcount/order%3DDESC/' .
			'limit%3D2000/-5B-5BCategory:Extension-5D-5D/-5B-5BHas-20extension-20type::!skin-5D-5D/' .
			'-3FHas-20website-20count/-3FHas-20standalone-20website-20count/-3FHas-20URL/';
		$this->fetchWikiapiary( $stats, $urlExt, 'extensions' );
		$urlSkin = 'https://wikiapiary.com/wiki/Special:Ask/format%3Djson/link%3Dall/' .
			'sort%3DHas-5Fwebsite-5Fcount/order%3DDESC/' .
			'limit%3D2000/-5B-5BCategory:Skin-5D-5D/' .
			'-3FHas-20website-20count/-3FHas-20standalone-20website-20count/-3FHas-20URL/' .
			'-3FHas-20website-20default-20count';
		$this->fetchWikiapiary( $stats, $urlSkin, 'skins' );
	}

	/**
	 * Helper function to actually do the WikiApiary stats
	 *
	 * @param array &$stats
	 * @param string $url URL to fetch from WikiApiary
	 * @param string $type Either "extensions' or 'skins'
	 */
	private function fetchWikiapiary( array &$stats, string $url, string $type ) {
		$context = stream_context_create( [ 'http' => [ 'user_agent' => 'toolforge/extjsonuploader' ] ] );
		$json = file_get_contents( $url, false, $context );
		if ( !$json ) {
			$this->logger->error( "Could not fetch wikiapiary $url" );
			// Wikiapiary is unstable, use cached version if possible
			$json = file_get_contents( __DIR__ . '/../public_html/wikiapiary-' . $type . '.json' );
			if ( !$json ) {
				$this->logger->error( "Could not fetch cached version" );
				exit( 1 );
			}
		}

		$data = json_decode( $json, true );
		if ( !$data || !isset( $data['results'] ) ) {
			$this->logger->error( "Could not parse wikiapiary data for $url" );
			exit( 1 );
		}
		// save a version in case wikiapiary goes down.
		file_put_contents( __DIR__ . '/../public_html/wikiapiary-' . $type . '.json', $json );
		$prevSiteCount = false;
		$rank = 0;
		foreach ( $data['results'] as $info ) {
			$name = $this->getName( $info );
			if ( !isset( $stats[$type][$name] ) ) {
				$stats[$type][$name] = [];
			}
			if ( $info['printouts']['Has website count'][0] !== $prevSiteCount ) {
				$rank++;
			}
			$stats[$type][$name]['siteCount'] = $info['printouts']['Has website count'][0];
			$stats[$type][$name]['siteCountRank'] = $rank;
			if ( isset( $info['printouts']['Has standalone website count'][0] ) ) {
				$stats[$type][$name]['siteCountStandalone'] = $info['printouts']['Has standalone website count'][0];
			}
			if ( isset( $info['printouts']['Has website default count'][0] ) ) {
				$stats[$type][$name]['siteCountDefault'] = $info['printouts']['Has website default count'][0];
			}
		}
	}

	/**
	 * Get extension name from WikiApiary output
	 * preferring mediawiki.org url if known.
	 *
	 * @param array $info
	 * @return string Extension/Skin name
	 */
	private function getName( array $info ) {
		$m = [];
		$pattern = '!^https?://www.mediawiki.org/wiki/Extension:([^?#]*)$!i';
		$url = $info['printouts']['Has URL'][0] ?? '';
		if ( preg_match( $pattern, $url, $m ) ) {
			return urldecode( $m[1] );
		}
		return preg_replace( '/^(Extension:|Skin:)/', '', $info['fulltext'] );
	}

	public function run() {
		$jsonSerializer = new JsonSerializer();
		$jsonSerializer->setLogger( $this->logger );

		$stats = $this->makeStatsDownloads();
		$this->doWikiapiary( $stats );

		// Save JSON to public_html.
		$jsonSerializer->serialize( $stats, __DIR__ . '/../public_html/ExtensionPopularity.json' );

		$wiki = new Wikimate( $this->apiUrl, [], [], [ 'timeout' => 30 ] );
		$wiki->setUserAgent( 'toolforge/extjsonuploader' );
		$res = $wiki->login( $this->wikiUser, $this->wikiPass );
		if ( !$res ) {
			$this->logger->error( 'Could not log in' );
			exit( 1 );
		}

		$page = $wiki->getPage( 'Template:Extension/popularity.json' );
		$saved = $page->setText( json_encode( $stats ), null, false, 'Resyncing from WikiApiary and Graphite' );
		if ( !$saved ) {
			$this->logger->error( 'Error when saving: ' . $page->getError()['info'] );
			exit( 1 );
		}
	}

}
