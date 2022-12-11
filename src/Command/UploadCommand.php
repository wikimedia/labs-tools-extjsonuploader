<?php

namespace MediaWiki\Tools\ExtensionJsonUploader\Command;

use MediaWiki\Tools\ExtensionJsonUploader\App;
use MediaWiki\Tools\ExtensionJsonUploader\StdErrLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wikimedia\Timestamp\ConvertibleTimestamp;

class UploadCommand extends Command {

	protected function configure() {
		$this->setName( 'upload' )
			->setDescription( 'Upload the extension data.' );
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		$config = require dirname( __DIR__, 2 ) . '/config.php';

		$app = new App(
			$config['extensionDirs'],
			$config['username'],
			$config['password']
		);
		$app->setLogger( new StdErrLogger() );
		if ( $config['apiUrl'] ) {
			$app->setApiUrl( $config['apiUrl'] );
		}

		$time = ConvertibleTimestamp::now( TS_POSTGRES );
		$output->writeln( "[$time] Starting upload..." );
		$app->run();
		$output->writeln( "...done." );

		return Command::SUCCESS;
	}
}
