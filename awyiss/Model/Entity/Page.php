<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Core\App;
use Awyiss\Model\Entity;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Text;


/**
 * Page Entity
 *
 * @property int $id
 * @property \Awyiss\Model\Enum\PageRoleEnumInterface|null $pageRoleId
 * @property int|null $pageTemplateId
 * @property int|null $parentId
 * @property string|null $languageShortcode
 * @property string|null $slug
 * @property string|null $link
 * @property string|null $title
 * @property string|null $redirectLink
 * @property string|null $metaTitle
 * @property string|null $metaDescription
 * @property bool $robotsIndex
 * @property bool $robotsFollow
 * @property int|null $duplicateOf
 * @property int|null $formId
 * @property int|null $surveyId
 * @property int $systemOrder
 * @property bool $active
 * @property bool $parentsActive
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\PageRole $pageRole
 * @property \Awyiss\Model\Entity\PageTemplate $pageTemplate
 * @property \Awyiss\Model\Entity\Page[] $duplicatingPages
 * @property \Awyiss\Model\Entity\Page $duplicateOfPage
 * @property \Awyiss\Model\Entity\Page $parentPage
 * @property \Awyiss\Model\Entity\Page[] $childPages
 * @property \Awyiss\Model\Entity\Content[] $contents
 * @property \Awyiss\Model\Entity\Form|null $form
 * @property \Awyiss\Model\Entity\Survey|null $survey
 * @property \Awyiss\Model\Entity\Language $language
 * @property array<int, int> $addMenuEntry
 */
class Page extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'parent_id' => 'parentId',
		'language_shortcode' => 'languageShortcode',
		'redirect_link' => 'redirectLink',
		'meta_title' => 'metaTitle',
		'meta_description' => 'metaDescription',
		'robots_index' => 'robotsIndex',
		'robots_follow' => 'robotsFollow',
		'page_role_id' => 'pageRoleId',
		'page_template_id' => 'pageTemplateId',
		'duplicate_of' => 'duplicateOf',
		'form_id' => 'formId',
		'survey_id' => 'surveyId',
		'system_order' => 'systemOrder',
		'parents_active' => 'parentsActive',
		'duplicated_by' => 'duplicatedBy',
		'page_role' => 'pageRole',
		'page_template' => 'pageTemplate',
		'add_menu_entry' => 'addMenuEntry',
	];
	/**
	 * @var bool
	 */
	protected static bool $includeLanguageShortcode;


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'parentId' => true,
		'slug' => true,
		'languageShortcode' => true,
		'title' => true,
		'redirectLink' => true,
		'metaTitle' => true,
		'metaDescription' => true,
		'robotsIndex' => true,
		'robotsFollow' => true,
		'pageRoleId' => true,
		'pageTemplateId' => true,
		'duplicateOf' => true,
		'formId' => true,
		'surveyId' => true,
		'systemOrder' => true,
		'active' => true,
		'addMenuEntry' => true,
	];
	/**
	 * @inheritdoc
	 */
	protected array $_virtual = ['link', 'label'];


	/**
	 * @inheritDoc
	 */
	public function __construct(array $properties = [], array $options = []) {
		parent::__construct($properties, $options);

		if (!isset(static::$includeLanguageShortcode)) {
			static::$includeLanguageShortcode = Configure::read('Route.includeLanguageShortcode');
		}
	}


	/**
	 * Get all direct children of the current entity
	 *
	 * @param array $options
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getChildren()
	 */
	public function getChildren(array $options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getChildren($this, $options);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @param array $options
	 * @param int $currentLevel
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getNestedChildren()
	 */
	public function getNestedChildren(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getNestedChildren($this, $options, $currentLevel);
	}


	/**
	 * Get the parent entity of the current entity
	 *
	 * @param array $options
	 * @return \Awyiss\Model\Entity\Widget|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParent()
	 */
	public function getParent(array $options = []): ?self {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParent($this, $options);
	}


	/**
	 * Get all the parent entities and all of its parent entities of the current entity
	 *
	 * @param array $options
	 * @param int $currentLevel
	 * @return \Cake\Collection\CollectionInterface|null
	 * @see \Awyiss\Model\Behavior\NestBehavior::getParents()
	 */
	public function getParents(array $options = [], int $currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParents($this, $options, $currentLevel);
	}


	/**
	 * @return string|null
	 */
	protected function _getLink(): ?string {
		if (!static::$includeLanguageShortcode) {
			return $this->slug;
		}

		return $this->languageShortcode . '/' . $this->slug;
	}


	/**
	 * @param array|null $menuIds
	 * @return array|null
	 */
	protected function _setAddMenuEntry(?array $menuIds): ?array {
		if (!$menuIds) {
			return null;
		}

		$la_menuIds = $menuIds;
		foreach ($la_menuIds as &$lx_menuId) {
			$lx_menuId = (int)$lx_menuId;
		}


		return $la_menuIds;
	}


	/**
	 * @param mixed $pageRoleId
	 * @return \Awyiss\Model\Enum\PageRoleEnumInterface|int|null
	 */
	protected function _setPageRoleId(mixed $pageRoleId): PageRoleEnumInterface|int|null {
		if (is_string($pageRoleId)) {
			return (int)$pageRoleId ?: null;
		}

		return $pageRoleId;
	}


	/**
	 * Make sure the slug is always lowercase, dashed and free of special characters
	 *
	 * @param string|null $slug
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Page::$slug
	 */
	protected function _setSlug(?string $slug): ?string {
		if ($slug === null) {
			return null;
		}

		$ls_slug = Text::slug($slug, ['preserve' => '/']);
		$ls_slug = trim($ls_slug, '/');

		if (str_contains($ls_slug, '/')) {
			$ls_slug = substr($ls_slug, strrpos($ls_slug, '/') + 1);
		}


		return mb_strtolower($ls_slug);
	}


	/**
	 * @inheritDoc
	 */
	public function defaultValues(): array {
		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		$la_parts = explode('\\', static::class);

		return parent::defaultValues() + [
			'pageRoleId' => $ls_pageRoleEnum::tryFromName(end($la_parts)),
		];
	}


	/**
	 * Check if the page has a content template that is usable
	 * in the current page template
	 *
	 * @return bool|null
	 */
	public function hasContentTemplate(): ?bool {
		if (!$this->pageTemplate?->contentAreas) {
			return null;
		}

		foreach ($this->pageTemplate->contentAreas as $lo_contentArea) {
			if ($lo_contentArea->contentTemplates) {
				return true;
			}
		}

		return false;
	}
}
