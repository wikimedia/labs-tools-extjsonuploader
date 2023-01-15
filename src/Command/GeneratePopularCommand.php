<?php

namespace MediaWiki\Tools\ExtensionJsonUploader\Command;

use MediaWiki\Tools\ExtensionJsonUploader\PopularityApp;
use MediaWiki\Tools\ExtensionJsonUploader\StdErrLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wikimedia\Timestamp\ConvertibleTimestamp;

class GeneratePopularCommand extends Command {

	protected function configure() {
		$this->setName( 'generatepopular' )
			->setDescription( 'Generate popularity extension data.' );
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		$config = require dirname( __DIR__, 2 ) . '/config.php';

		$app = new PopularityApp(
			$config['username'],
			$config['password']
		);
		$app->setLogger( new StdErrLogger() );

		$time = ConvertibleTimestamp::now( TS_POSTGRES );
		$output->writeln( "[$time] Starting popularity..." );
		$app->run();
		$output->writeln( "...done." );

		return Command::SUCCESS;
	}
}
