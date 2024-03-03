<?php declare(strict_types=1);


namespace Awyiss\Command\Phinx;


use Awyiss\Command\Util\OperationsPathTrait;
use Migrations\Command\Phinx\Migrate as BaseMigrate;
use Symfony\Component\Console\Input\InputOption;


/**
 * @inheritDoc
 */
class Migrate extends BaseMigrate {
	use OperationsPathTrait;


	/**
	 * Adds the `folder`-option
	 * Removes the `source`-option
	 *
	 * @inheritDoc
	 * @return void
	 */
	protected function configure(): void {
		$this->setName('migrate')
			->setDescription('Migrate the database')
			->setHelp('runs all available migrations, optionally up to a specific version')
			->addOption(
				'--target',
				'-t',
				InputOption::VALUE_REQUIRED,
				'The version number to migrate to'
			)
			->addOption('--date', '-d', InputOption::VALUE_REQUIRED, 'The date to migrate to')
			->addOption(
				'--dry-run',
				'-x',
				InputOption::VALUE_NONE,
				'Dump queries to standard output instead of executing it'
			)
			->addOption(
				'--plugin',
				'-p',
				InputOption::VALUE_REQUIRED,
				'The plugin containing the migrations'
			)
			->addOption('--connection', '-c', InputOption::VALUE_REQUIRED, 'The datasource connection to use')
			//->addOption('--source', '-s', InputOption::VALUE_REQUIRED, 'The folder where migrations are in')
			->addOption('--folder', null, InputOption::VALUE_REQUIRED, 'The folder where migrations are in')
			->addOption(
				'--fake',
				null,
				InputOption::VALUE_NONE,
				"Mark any migrations selected as run, but don't actually execute them"
			)
			->addOption(
				'--no-lock',
				null,
				InputOption::VALUE_NONE,
				'If present, no lock file will be generated after migrating'
			);
	}
}
