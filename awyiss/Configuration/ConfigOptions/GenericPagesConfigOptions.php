<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Model\Behavior\Date\DateType;


/**
 * Provides all configuration options for the ContentTemplates scope
 */
class GenericPagesConfigOptions extends AbstractConfigOptions {
	/**
	 * @var string|null
	 */
	protected ?string $pageRole = null;


	/**
	 * @var string Scope of these options
	 */
	protected static string $scope = 'GenericPages';


	/**
	 * @inheritDoc
	 */
	public function initializeConfigOptions(): void {
		$this->add(Awyiss::REALM_BACKEND, [
			'dates' => [
				'types' => [
					new ConfigOption([
						'defaultValue' => DateType::DATE,
						'identifier' => 'eventStart',
						'localizable' => false,
						'nullable' => true,
						'type' => ConfigOptionType::ENUM,
						'values' => DateType::class,
					]),
					new ConfigOption([
						'defaultValue' => null,
						'identifier' => 'eventEnd',
						'localizable' => false,
						'nullable' => true,
						'type' => ConfigOptionType::ENUM,
						'values' => DateType::class,
					]),
					new ConfigOption([
						'defaultValue' => DateType::DATETIME,
						'identifier' => 'publicationStart',
						'localizable' => false,
						'nullable' => true,
						'type' => ConfigOptionType::ENUM,
						'values' => DateType::class,
					]),
					new ConfigOption([
						'defaultValue' => DateType::DATETIME,
						'identifier' => 'publicationEnd',
						'localizable' => false,
						'nullable' => true,
						'type' => ConfigOptionType::ENUM,
						'values' => DateType::class,
					]),
				],
			],
			'paginate' => [
				new ConfigOption([
					'defaultValue' => true,
					'identifier' => 'enabled',
					'localizable' => false,
					'nullable' => true,
					'type' => ConfigOptionType::BOOL,
				]),
				new ConfigOption([
					'defaultValue' => 20,
					'identifier' => 'limit',
					'localizable' => false,
					'nullable' => false,
					'type' => ConfigOptionType::INTEGER,
				]),
			],
			'systemOrder' => [
				new ConfigOption([
					'defaultValue' => SORT_ASC,
					'identifier' => 'direction',
					'localizable' => false,
					'nullable' => false,
					'type' => ConfigOptionType::LISTVALUE,
					'values' => [
						SORT_ASC,
						SORT_DESC,
					],
				]),
				new ConfigOption([
					'defaultValue' => 'title',
					'identifier' => 'field',
					'localizable' => false,
					'nullable' => false,
					'type' => ConfigOptionType::LISTVALUE,
					'values' => $this->getSystemOrderFields(...),
				]),
			],
		]);
	}


	/**
	 * @return string|null
	 */
	public function getPageRole(): ?string {
		return $this->pageRole;
	}


	/**
	 * @param string|null $as_pageRole
	 * @return $this
	 */
	public function setPageRole(?string $as_pageRole): static {
		$this->pageRole = $as_pageRole;


		return $this;
	}
}
