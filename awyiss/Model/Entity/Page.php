<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Text;


/**
 * Page Entity
 *
 * @property int $id
 * @property int $parentId
 * @property string|null $slug
 * @property string|null $languageShortcode
 * @property string|null $title
 * @property string|null $redirectLink
 * @property string|null $metaTitle
 * @property string|null $metaDescription
 * @property bool $robotsIndex
 * @property bool $robotsFollow
 * @property int $pageRoleId
 * @property int $pageTemplateId
 * @property int|null $duplicateOf
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
 * @property Attribute $attributes
 * @property PageRole $pageRole
 * @property PageTemplate $pageTemplate
 * @property Page $parentPage
 * @property Page $duplicate
 * @property Page[] $children
 * @property Content[] $contents
 */
class Page extends Entity {
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
		'systemOrder' => true,
		'active' => true,
		'parentsActive' => true,
		'pageRole' => true,
		'pageTemplate' => true,
		'contents' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $defaultValues = [
		'pageRoleId' => PAGEROLE_PAGE,
	];
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
		'system_order' => 'systemOrder',
		'parents_active' => 'parentsActive',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'duplicate_pages' => 'duplicatePages',
		'duplicate_of_page' => 'duplicateOfPage',
		'child_pages' => 'childPages',
		'parent_page' => 'parentPage',
		'page_role' => 'pageRole',
		'page_template' => 'pageTemplate',
	];


	/**
	 * Get all direct children of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getChildren(array $aa_options = []): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getChildren($this, $aa_options);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @noinspection PhpUnused
	 */
	public function getNestedChildren(array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getNestedChildren($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * Get the parent page of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent(array $aa_options = []): ?self {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParent($this, $aa_options);
	}


	/**
	 * Get all the parent page and all of its parents pages of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParents(array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());


		return $lo_table->getParents($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * Make sure the slug is always lowercase, dashed and free of special characters
	 *
	 * @noinspection PhpUnused
	 * @see \Awyiss\Model\Entity\Page::$slug
	 */
	protected function _setSlug(string $as_slug): string {
		$ls_slug = Text::slug($as_slug, ['preserve' => '/']);
		$ls_slug = trim($ls_slug, '/');

		if (str_contains($ls_slug, '/')) {
			$ls_slug = substr($ls_slug, strrpos($ls_slug, '/') + 1);
		}


		return mb_strtolower($ls_slug);
	}
}
