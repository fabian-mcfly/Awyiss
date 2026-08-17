<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptions\GenericDatatablesConfigOptions;
use Awyiss\Configuration\ConfigOptionsProvider;
use Awyiss\Model\Entity\Configuration;
use Cake\Database\Exception\DatabaseException;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * Event listeners for the general events of the backend
 */
class GeneralEventsListener implements EventListenerInterface {
	use LocatorAwareTrait;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Configuration.afterSave' => 'afterConfigurationSaveCommit',
			'Model.Configuration.afterDelete' => 'afterConfigurationDeleteCommit',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $configuration
	 * @return void
	 * @noinspection PhpUnused,PhpUnusedParameterInspection
	 */
	public function afterConfigurationSaveCommit(Event $event, Configuration $configuration): void {
		if (
			$configuration->realm !== Awyiss::REALM_BACKEND
			|| $configuration->identifier !== 'splitIntoLanguages'
		) {
			return;
		}

		// If configuration is not for a generic datatable or value is true, do nothing as we cannot move entries to a specific language
		$configOptions = ConfigOptionsProvider::loadConfigOptions($configuration->scope);
		if (
			!$configOptions instanceof GenericDatatablesConfigOptions
			|| (bool)$configuration->value === true
		) {
			return;
		}

		try {
			$table = $this->fetchTable($configuration->scope);
		}
		catch (MissingTableClassException | DatabaseException) {
			return;
		}

		$table->updateAll([
			'languageShortcode' => null,
		], [
			'languageShortcode IS NOT' => null,
			'deleted' => false,
		]);
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Configuration $configuration
	 * @return void
	 * @noinspection PhpUnused,PhpUnusedParameterInspection
	 */
	public function afterConfigurationDeleteCommit(Event $event, Configuration $configuration): void {
		if (
			$configuration->realm !== Awyiss::REALM_BACKEND
			|| $configuration->identifier !== 'splitIntoLanguages'
		) {
			return;
		}

		$configOptions = ConfigOptionsProvider::loadConfigOptions($configuration->scope);
		if (!$configOptions instanceof GenericDatatablesConfigOptions) {
			return;
		}

		$configOption = $configOptions->getConfigOption(Awyiss::REALM_BACKEND, $configuration->identifier);
		if (!$configOption) {
			return;
		}

		$defaultSplit = $configOption->getDefaultValue() ?? false;

		// If default is true, do nothing as we cannot move entries to a specific language
		if ($defaultSplit) {
			return;
		}

		try {
			$table = $this->fetchTable($configuration->scope);
		}
		catch (MissingTableClassException | DatabaseException) {
			return;
		}

		$table->updateAll([
			'languageShortcode' => null,
		], [
			'languageShortcode IS NOT' => null,
			'deleted' => false,
		]);
	}
}
