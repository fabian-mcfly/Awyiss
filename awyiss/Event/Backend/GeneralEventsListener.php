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
			$configuration->realm !== Awyiss::REALM_BACKEND ||
			$configuration->identifier !== 'split_into_languages'
		) {
			return;
		}

		// If configuration is not for a generic datatable or value is true, do nothing as we cannot move entries to a specific language
		$lo_configuration = ConfigOptionsProvider::loadConfigOptions($configuration->scope);
		if (
			!$lo_configuration instanceof GenericDatatablesConfigOptions ||
			(bool)$configuration->value === true
		) {
			return;
		}

		try {
			$lo_table = $this->fetchTable($configuration->scope);
		}
		catch (MissingTableClassException | DatabaseException) {
			return;
		}

		$lo_table->updateAll([
			'language_shortcode' => null,
		], [
			'language_shortcode IS NOT' => null,
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
			$configuration->realm !== Awyiss::REALM_BACKEND ||
			$configuration->identifier !== 'split_into_languages'
		) {
			return;
		}

		$lo_configuration = ConfigOptionsProvider::loadConfigOptions($configuration->scope);
		if (!$lo_configuration instanceof GenericDatatablesConfigOptions) {
			return;
		}

		$lo_configOption = $lo_configuration->getConfigOption(Awyiss::REALM_BACKEND, $configuration->identifier);
		if (!$lo_configOption) {
			return;
		}

		$lb_defaultSplit = $lo_configOption->getDefaultValue() ?? false;

		// If default is true, do nothing as we cannot move entries to a specific language
		if ($lb_defaultSplit) {
			return;
		}

		try {
			$lo_table = $this->fetchTable($configuration->scope);
		}
		catch (MissingTableClassException | DatabaseException) {
			return;
		}

		$lo_table->updateAll([
			'language_shortcode' => null,
		], [
			'language_shortcode IS NOT' => null,
			'deleted' => false,
		]);
	}
}
