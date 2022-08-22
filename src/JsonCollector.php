<?php

namespace MediaWiki\Tools\ExtensionJsonUploader;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;

/**
 * Collect all extension.json data into an array.
 */
class JsonCollector implements LoggerAwareInterface {

	use LoggerAwareTrait;

	/** @var string[] */
	protected $extensionsDirs;

	/**
	 * @param string[] $extensionsDirs List of extensions directories
	 *   (e.g. clone of https://gerrit.wikimedia.org/r/mediawiki/extensions )
	 */
	public function __construct( $extensionsDirs ) {
		$this->extensionsDirs = $extensionsDirs;
		$this->logger = new NullLogger();
	}

	/**
	 * Returns all extension.json data, in an associative array keyed by
	 * extension name (which tries to match the wiki name if possible).
	 * @return array
	 */
	public function collect() {
		$overall = [];
		$dupeNames = [];
		foreach ( $this->getFiles() as $file ) {
			$filetext = file_get_contents( $file );
			$ext = json_decode( $filetext, true );
			if ( !$ext ) {
				continue;
			}

			// Keep size down under the 2MB limit of mediawiki.org
			unset(
				$ext['AutoloadClasses'],
				$ext['TestAutoloadClasses'],
				$ext['AutoloadNamespaces'],
				$ext['TestAutoloadNamespaces'],
				$ext['MessagesDirs'],
				$ext['ResourceFileModulePaths'],
				$ext['ResourceModules'],
				$ext['load_composer_autoloader'],
				$ext['manifest_version']
			);

			// Add Git repository URL from Composer or the Git remote definition.
			$composerJson = dirname( $file ) . '/composer.json';
			if ( file_exists( $composerJson ) ) {
				$composerContents = file_get_contents( $composerJson );
				$composer = json_decode( $composerContents, true );
				if ( isset( $composer['support']['source'] ) ) {
					$ext['repository'] = $composer['support']['source'];
				}
				if ( isset( $composer['name'] ) ) {
					$ext['composer'] = $composer['name'];
				}
			}
			if ( !isset( $ext['repository'] ) ) {
				$remoteUrl = $this->getRemoteUrl( $file );
				if ( $remoteUrl ) {
					$ext['repository'] = $remoteUrl;
				}
			}

			$name = $this->getName( $ext );
			if ( !$name ) {
				$this->logger->error( "$file has no name" );
				continue;
			}
			// Keep track of seen extension names.
			$dupeNames[$name] = isset( $dupeNames[$name] ) ? $dupeNames[$name] : [];
			$dupeNames[$name][] = $file;
			if ( isset( $overall[ $name ] ) ) {
				$fileList = '  - ' . implode( "\n  - ", $dupeNames[ $name ] );
				$this->logger->error( "Duplicate extension name '$name' detected in these files:\n" . $fileList );
			}
			$overall[$name] = $ext;
		}
		return $overall;
	}

	/**
	 * Get the URL of the Git 'origin' repository, from an extension.json filename.
	 * @param string $file The filesystem path to the extension.json file.
	 * @return string|null
	 */
	private function getRemoteUrl( string $file ): ?string {
		$process = new Process( [ 'git', 'remote', 'get-url', 'origin' ] );
		$process->setWorkingDirectory( dirname( $file ) );
		$process->setTimeout( null );
		$process->run();
		return trim( $process->getOutput() ) ?: null;
	}

	/**
	 * Iterate through extension directories and list the files.
	 * @return Generator<string>
	 */
	protected function getFiles() {
		foreach ( $this->extensionsDirs as $dir ) {
			$dir = rtrim( $dir, '/' );
			foreach ( glob( $dir . '/*/extension.json' ) as $file ) {
				yield $file;
			}
		}
	}

	/**
	 * Name to include under. We want to match up with MW pages,
	 * so prefer url of extension page over actual name.
	 * @param array|false $json Extension data
	 * @return string|false
	 */
	protected function getName( array $json ) {
		$m = [];
		$pattern = '!^https?://www.mediawiki.org/wiki/Extension:([^?#]*)$!i';
		if ( isset( $json['url'] ) && preg_match( $pattern, $json['url'], $m ) ) {
			return urldecode( $m[1] );
		} elseif ( isset( $json['name'] ) && is_string( $json['name'] ) ) {
			return $json['name'];
		}
		return false;
	}

}
