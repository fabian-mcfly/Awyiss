<?php declare(strict_types=1);


namespace Awyiss\Configuration\ConfigOptions;


use Awyiss\Awyiss;
use Awyiss\Configuration\AbstractGenericConfigOptions;
use Awyiss\Configuration\ConfigOption;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;
use Awyiss\Configuration\ConfigOptions\Trait\TableNamesTrait;
use Awyiss\Configuration\ConfigOptionType;
use Awyiss\Utility\Inflector;
use Cake\ORM\Locator\LocatorAwareTrait;


/**
 * Provides all configuration options for generic pages
 */
class GenericPagesConfigOptions extends AbstractGenericConfigOptions {
	use LocatorAwareTrait;
	use TableFieldsTrait;
	use TableNamesTrait;


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
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: false,
					identifier: 'allowUnassigned',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'associationName',
					localizable: false,
					type: ConfigOptionType::ListKey,
					values: $this->getTableNames(...),
				),
				new ConfigOption(
					defaultValue: null,
					identifier: 'categories',
					localizable: false,
					type: ConfigOptionType::JsonArray,
				),
				new ConfigOption(
					defaultValue: 'category',
					identifier: 'identifier',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::String
				),
				new ConfigOption(
					defaultValue: false,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool
				),
				new ConfigOption(
					defaultValue: false,
					identifier: 'includeParentCategories',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool
				),
				new ConfigOption(
					defaultValue: true,
					identifier: 'threaded',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool
				),
				new ConfigOption(
					defaultValue: true,
					identifier: 'useDatasource',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
			],
			'contents' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
			],
			'mediaFolders' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'autoCreate',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool
				),
			],
			'overview' => [
				new ConfigOption(
					defaultValue: [
						'page_template_id',
					],
					identifier: 'displayedFields',
					localizable: false,
					personalizable: true,
					type: ConfigOptionType::ValueCollection,
					values: function () {
						$la_fields = $this->getTableFields();

						unset($la_fields['id'], $la_fields['title'], $la_fields['slug']);

						return $la_fields;
					},
				),
			],
			'nest' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
			],
			'paginate' => [
				new ConfigOption(
					defaultValue: false,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::Bool,
				),
				new ConfigOption(
					defaultValue: 20,
					identifier: 'limit',
					localizable: false,
					nullable: false,
					personalizable: true,
					type: ConfigOptionType::Integer,
				),
			],
			'publicationData' => [
				new ConfigOption(
					defaultValue: true,
					identifier: 'enabled',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::Bool,
				),
			],
			'systemOrder' => [
				new ConfigOption(
					defaultValue: SORT_ASC,
					identifier: 'direction',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::ListKey,
					typecast: function (mixed $value): string|int|null {
						if ($value === 'asc' || intval($value) === SORT_ASC) {
							return SORT_ASC;
						}

						if ($value === 'desc' || intval($value) === SORT_DESC) {
							return SORT_DESC;
						}


						return null;
					},
					values: [
						SORT_ASC => __d(Inflector::underscore(static::getScope()), 'sort_asc'),
						SORT_DESC => __d(Inflector::underscore(static::getScope()), 'sort_desc'),
					],
				),
				new ConfigOption(
					defaultValue: 'title',
					identifier: 'field',
					localizable: false,
					nullable: false,
					type: ConfigOptionType::ListKey,
					values: $this->getTableFields(...),
				),
			],
		]);

		$this->add(Awyiss::REALM_FRONTEND, [
			'categories' => [
				/**
				 * This option should normally be in the backend realm,
				 * but it is needed in the frontend realm to
				 * use the correct languages
				 */
				new ConfigOption(
					defaultValue: null,
					identifier: 'forcedRootPageId',
					localizable: true,
					nullable: true,
					type: ConfigOptionType::ListKey,
					values: $this->getPages(...),
				),
			],
			'mediaFolders' => [
				/**
				 * This option should normally be in the backend realm,
				 * but it is required to be in the frontend realm to
				 * use the correct languages
				 */
				new ConfigOption(
					defaultValue: null,
					identifier: 'parentFolderId',
					localizable: true,
					nullable: true,
					type: ConfigOptionType::ListKey,
					values: $this->getMediaFolders(...),
				),
			],
		]);
	}


	/**
	 * Returns a list of all media folders
	 */
	protected function getMediaFolders(?string $languageShortcode): array {
		$lo_mediaFoldersTable = $this->fetchTable('MediaFolders');
		$lo_mediaFolders = $lo_mediaFoldersTable->find('forCurrentLanguage', languageShortcode: $languageShortcode)->find('threaded')->where([
			'id !=' => 1,
			'hidden' => false,
			'OR' => [
				'language_shortcode' . ($languageShortcode === null ? ' IS' : '') => $languageShortcode,
			],
		])->all();
		$lo_mediaFolders = $lo_mediaFolders->listNested();

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		foreach ($lo_mediaFolders as $lo_page) {
			$lo_page->setVirtual(['level']);
			//Add the current depth as a level-property to the entity
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_page->level = $lo_mediaFolders->getDepth();
		}

		return $lo_mediaFolders->printer('label', 'id', '- ')->toArray();
	}


	/**
	 * Returns a list of all pages
	 */
	protected function getPages(?string $languageShortcode): array {
		$lo_pagesTable = $this->fetchTable('Pages');
		$lo_pages = $lo_pagesTable->find('forCurrentLanguage', languageShortcode: $languageShortcode)->find('threaded')->all();
		$lo_pages = $lo_pages->listNested();

		/** @var \Awyiss\Model\Entity\Page $lo_page */
		foreach ($lo_pages as $lo_page) {
			$lo_page->setVirtual(['level']);
			//Add the current depth as a level-property to the entity
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_page->level = $lo_pages->getDepth();
		}

		return $lo_pages->printer('label', 'id', '- ')->toArray();
	}
}
