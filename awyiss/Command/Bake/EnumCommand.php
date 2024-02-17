<?php declare(strict_types=1);


namespace Awyiss\Command\Bake;


use Awyiss\Command\Util\UtilTrait;
use Bake\Command\EnumCommand as BaseBakeEnumCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Utility\Inflector;


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
	 * @param \Cake\Console\Arguments $ao_args The arguments for the command
	 * @return array
	 * @phpstan-return array<string, mixed>
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function templateData(Arguments $ao_args): array {
		$la_data = parent::templateData($ao_args);

		$ls_namespace = Inflector::camelize($ao_args->getOption('namespace') ?: Configure::read('App.namespace'));
		$la_data['namespace'] = $ls_namespace;

		$la_data['isPageRole'] = $ao_args->getOption('is-pagerole');


		return $la_data;
	}


	/**
	 * @inheritDoc
	 * @param string $as_name
	 * @param \Cake\Console\Arguments $ao_args
	 * @param \Cake\Console\ConsoleIo $ao_io
	 * @return void
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function bake(string $as_name, Arguments $ao_args, ConsoleIo $ao_io): void {
		$ls_name = Inflector::camelize($as_name);

		parent::bake($ls_name, $ao_args, $ao_io);
	}


	/**
	 * Adds the `namespace`-option.
	 *
	 * @inheritDoc
	 * @param ConsoleOptionParser $ao_parser
	 * @return ConsoleOptionParser
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildOptionParser(ConsoleOptionParser $ao_parser): ConsoleOptionParser {
		$lo_parser = parent::buildOptionParser($ao_parser);

		$lo_parser->addOption('namespace', [
			'help' => 'The namespace for the model. Should be either "Awyiss" or <CUSTOM_NAMESPACE>',
		])->addOption('is-pagerole', [
			'boolean' => true,
			'help' => 'Does the enum reflect pagerole values?fo',
		]);


		return $lo_parser;
	}
}
