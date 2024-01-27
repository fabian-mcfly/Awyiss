<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\SystemOrderFieldsTrait;
use Awyiss\Configuration\ConfigOptionType;


/**
 * Provides all configuration options for the ContentTemplates scope
 */
class GenericPagesConfigOptions extends AbstractConfigOptions {
	use SystemOrderFieldsTrait;


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
			'categories' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'allowAggregation',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::BOOL,
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'associationName',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::STRING,
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'categories',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::JSON_ARRAY,
				),
				new ConfigOption(
					defaultValue: 'category',
					identifier: 'identifier',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::STRING
				),
				new ConfigOption(
					defaultValue: false,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::BOOL
				),
				new ConfigOption(
					defaultValue: true,
					identifier: 'useDatasource',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::BOOL,
				),
			],
			'contents' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::BOOL,
				),
			],
			'paginate' => [
				new ConfigOption(
					defaultValue: true,
					identifier: 'enabled',
					localizable: false,
					nullable: true,
					type: ConfigOptionType::BOOL,
				),
				new ConfigOption(
					defaultValue: 20,
					identifier: 'limit',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::INTEGER,
				),
			],
			'systemOrder' => [
				new ConfigOption(
					defaultValue: SORT_ASC,
					identifier: 'direction',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::LISTVALUE,
					values: [
						SORT_ASC,
						SORT_DESC,
					],
				),
				new ConfigOption(
					defaultValue: 'title',
					identifier: 'field',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::LISTVALUE,
					values: $this->getSystemOrderFields(...),
				),
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
