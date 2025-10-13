<?php declare(strict_types=1);


namespace Awyiss\Command\Scss;


use Awyiss\Core\App;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\FactoryLocator;
use InvalidArgumentException;


/**
 * Compiles SCSS files to CSS
 */
class CompileCommand extends Command {
	/**
	 * @var class-string<\Awyiss\Utility\Design\ScssCompiler>
	 */
	protected string $compilerClass;


	/**
	 * @inheritDoc
	 */
	public static function getDescription(): string {
		return 'Compiles SCSS files to CSS';
	}


	/**
	 * @inheritDoc
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($parser);

		$lo_parser->addOption('realm', [
			'choices' => [
				'Backend',
				'Frontend',
			],
			'default' => 'Frontend',
			'help' => 'The Realm to compile (Frontend or Backend)',
			'short' => 'r',
		]);


		return $lo_parser;
	}


	/**
	 * @inheritDoc
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return int
	 * @throws \ScssPhp\ScssPhp\Exception\SassException
	 */
	public function execute(Arguments $args, ConsoleIo $io): int {
		$io->out(sprintf('Fetching folders for realm `%s`... ', $args->getOption('realm')), 0);

		/** @var class-string<\Awyiss\Utility\Design\ScssCompiler> $ls_compilerClass */
		$ls_compilerClass = $this->getCompilerClass();

		// Set the exception handling for the ScssCompiler
		$ls_compilerClass::showExceptions(true);

		// Discover the SCSS files in the realm
		try {
			$la_files = $ls_compilerClass::discoverRealmFiles($args->getOption('realm'));
		}
		catch (InvalidArgumentException) {
			$io->err('No files found.');

			return static::CODE_ERROR;
		}

		$io->out(sprintf('%d folders found.', count($la_files)));

		$la_designSettings = [];

		if ($args->getOption('realm') == 'Frontend') {
			$lo_designTable = FactoryLocator::get('Table')->get('Designs');
			/** @var \Awyiss\Model\Entity\Design $lo_design */
			$lo_design = $lo_designTable->find()->where(['in_use' => true])->first();
			$la_designSettings = $lo_design->settings ?? [];
		}

		// Compile the SCSS files
		if ($ls_compilerClass::compileFolders($la_files, $la_designSettings)) {
			$io->success('All files compiled successfully.');

			return static::CODE_SUCCESS;
		}

		$io->err('An error occurred while compiling the files.');

		return static::CODE_ERROR;
	}


	/**
	 * @return class-string<\Awyiss\Utility\Design\ScssCompiler>
	 */
	protected function getCompilerClass(): string {
		if (isset($this->compilerClass)) {
			return $this->compilerClass;
		}

		/** @var class-string<\Awyiss\Utility\Design\ScssCompiler> $ls_className */
		$this->compilerClass = App::className('ScssCompiler', 'Utility/Design');

		return $this->compilerClass;
	}
}
