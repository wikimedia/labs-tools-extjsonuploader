<?php

namespace MediaWiki\Tools\ExtensionJsonUploader\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Wikimedia\Timestamp\ConvertibleTimestamp;

class UpdateCommand extends Command {

	protected function configure() {
		$this->setName( 'update' )
			->setDescription( 'Update the extension repositories.' );
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		$config = require dirname( __DIR__, 2 ) . '/config.php';
		$extensionDirs = $config['extensionDirs'] ?? [];
		$time = ConvertibleTimestamp::now( TS_POSTGRES );
		$output->writeln( "[$time] Starting update..." );
		foreach ( $extensionDirs as $extensionDir ) {
			if ( !is_dir( $extensionDir ) ) {
				throw new RuntimeException( 'Directory not found: ' . $extensionDir );
			}
			$output->writeln( 'Updating ' . $extensionDir );

			$process = new Process( [ 'git', 'fetch', '--quiet', '--depth', '1' ] );
			$process->setWorkingDirectory( $extensionDir );
			$process->setTimeout( null );
			$process->mustRun();

			$process = new Process( [ 'git', 'reset', '--quiet', '--hard', 'origin/master' ] );
			$process->setWorkingDirectory( $extensionDir );
			$process->setTimeout( null );
			$process->mustRun();

			$process = new Process( [ 'git', 'submodule', 'sync' ] );
			$process->setWorkingDirectory( $extensionDir );
			$process->setTimeout( null );
			$process->mustRun();

			$process = new Process( [ 'git', 'submodule', '--quiet', 'update', '--init', '--depth', '1' ] );
			$process->setWorkingDirectory( $extensionDir );
			$process->setTimeout( null );
			$process->mustRun();

			$process = new Process( [ 'git', 'clean', '--quiet', '-d', '-ff' ] );
			$process->setWorkingDirectory( $extensionDir );
			$process->setTimeout( null );
			$process->mustRun();
		}
		$time = ConvertibleTimestamp::now( TS_POSTGRES );
		$output->writeln( "[$time] ...update finished." );
		return Command::SUCCESS;
	}
}
