<?php declare(strict_types=1);


namespace Awyiss\Command\Bake;


use Awyiss\Command\Util\UtilTrait;
use Awyiss\Utility\Inflector;
use Bake\Command\EnumCommand as BaseBakeEnumCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;


/**
 * Enum code generator.
 */
class EnumCommand extends BaseBakeEnumCommand {
	/*
	 * Use UtilTrait so that every call of `$this->getPath()` will use the one provided by this trait,
	 * honoring the `namespace`-option
	 */
	use UtilTrait;


	/**
	 * @inheritDoc
	 */
	public function template(): string {
		return 'Model/enum';
	}


	/**
	 * Get template data.
	 *
	 * @param \Cake\Console\Arguments $args The arguments for the command
	 * @return array
	 * @phpstan-return array<string, mixed>
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function templateData(Arguments $args): array {
		$data = parent::templateData($args);

		$namespace = Inflector::camelize($args->getOption('namespace') ?: Configure::read('App.namespace'));
		$data['namespace'] = $namespace;

		$data['isPageRole'] = $args->getOption('is-pagerole');


		return $data;
	}


	/**
	 * @inheritDoc
	 * @param string $name
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function bake(string $name, Arguments $args, ConsoleIo $io): void {
		parent::bake(Inflector::camelize($name), $args, $io);
	}


	/**
	 * Adds the `namespace`-option.
	 *
	 * @inheritDoc
	 * @param ConsoleOptionParser $parser
	 * @return ConsoleOptionParser
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		$parser = parent::buildOptionParser($parser);

		$parser->addOption('namespace', [
			'choices' => [
				'Awyiss',
				CUSTOM_NAMESPACE,
			],
			'default' => 'Awyiss',
			'help' => 'The namespace for the model.',
		])->addOption('is-pagerole', [
			'boolean' => true,
			'help' => 'Does the enum reflect pagerole values?',
		]);


		return $parser;
	}
}
