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
					type: ConfigOptionType::Json,
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
			'forms' => [
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
						$fields = $this->getTableFields();

						unset($fields['id'], $fields['title'], $fields['slug']);

						return $fields;
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
			'surveys' => [
				new ConfigOption(
					defaultValue: false,
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
						SORT_ASC => __d(Inflector::underscore($this->getDynamicScope()), 'sort_asc'),
						SORT_DESC => __d(Inflector::underscore($this->getDynamicScope()), 'sort_desc'),
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
		$mediaFoldersTable = $this->fetchTable('MediaFolders');
		/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
		$query = $mediaFoldersTable->find('forCurrentLanguage', languageShortcode: $languageShortcode ?? false, includeGlobal: false)->where([
			'id !=' => 1,
			'hidden' => false,
		]);
		/** @var \Cake\Collection\Iterator\TreeIterator $mediaFolders */
		$mediaFolders = $mediaFoldersTable->listNested($query);

		return $mediaFolders->printer('label', 'id', '- ')->toArray();
	}


	/**
	 * Returns a list of all pages
	 */
	protected function getPages(?string $languageShortcode): array {
		$pagesTable = $this->fetchTable('Pages');
		/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
		$query = $pagesTable->find('forCurrentLanguage', languageShortcode: $languageShortcode);
		/** @var \Cake\Collection\Iterator\TreeIterator $pages */
		$pages = $pagesTable->listNested($query);

		return $pages->printer('label', 'id', '- ')->toArray();
	}
}
