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
		$parser = parent::buildOptionParser($parser);

		$parser->addOption('realm', [
			'choices' => [
				'Backend',
				'Frontend',
			],
			'default' => 'Frontend',
			'help' => 'The Realm to compile (Frontend or Backend)',
			'short' => 'r',
		]);


		return $parser;
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

		/** @var class-string<\Awyiss\Utility\Design\ScssCompiler> $compilerClass */
		$compilerClass = $this->getCompilerClass();

		// Set the exception handling for the ScssCompiler
		$compilerClass::showExceptions(true);

		// Discover the SCSS files in the realm
		try {
			$files = $compilerClass::discoverRealmFiles($args->getOption('realm'));
		}
		catch (InvalidArgumentException) {
			$io->err('No files found.');

			return static::CODE_ERROR;
		}

		$io->out(sprintf('%d folders found.', count($files)));

		$designSettings = [];

		if ($args->getOption('realm') == 'Frontend') {
			$designTable = FactoryLocator::get('Table')->get('Designs');
			/** @var \Awyiss\Model\Entity\Design $design */
			$design = $designTable->find()->where(['in_use' => true])->first();
			$designSettings = $design->settings ?? [];
		}

		// Compile the SCSS files
		if ($compilerClass::compileFolders($files, $designSettings)) {
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
