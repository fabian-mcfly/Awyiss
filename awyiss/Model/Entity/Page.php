<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Model\Table\PagesTable;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\FrozenTime;
use Cake\Utility\Text;


/**
 * Page Entity
 *
 * @property int $id
 * @property int $parentId
 * @property string|NULL $slug
 * @property string|NULL $languageShortcode
 * @property string|NULL $title
 * @property string|NULL $redirectLink
 * @property string|NULL $metaTitle
 * @property string|NULL $metaDescription
 * @property bool $robotsIndex
 * @property bool $robotsFollow
 * @property int $pageRoleId
 * @property int $pageTemplateId
 * @property int|NULL $duplicateOf
 * @property int $systemOrder
 * @property bool $active
 * @property bool $parentsActive
 * @property bool $deleted
 * @property int|NULL $createdBy
 * @property FrozenTime|NULL $createdOn
 * @property int|NULL $changedBy
 * @property FrozenTime|NULL $changedOn
 * @property int|NULL $deletedBy
 * @property FrozenTime|NULL $deletedOn
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
		'parentId' => TRUE,
		'slug' => TRUE,
		'languageShortcode' => TRUE,
		'title' => TRUE,
		'redirectLink' => TRUE,
		'metaTitle' => TRUE,
		'metaDescription' => TRUE,
		'robotsIndex' => TRUE,
		'robotsFollow' => TRUE,
		'pageRoleId' => TRUE,
		'pageTemplateId' => TRUE,
		'duplicateOf' => TRUE,
		'systemOrder' => TRUE,
		'active' => TRUE,
		'parentsActive' => TRUE,
		'pageRole' => TRUE,
		'pageTemplate' => TRUE,
		'contents' => TRUE,
	];
	/**
	 * @inheritDoc
	 */
	protected array $defaults = [
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
	public function getChildren (): ?CollectionInterface {
		/** @var PagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getChildren($this);
	}


	/**
	 * Get all children, and their children, and their children, and their children of the current entity. And its children.
	 *
	 * @noinspection PhpUnused
	 */
	public function getNestedChildren (array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var PagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getNestedChildren($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * Get the parent page of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParent (): ?self {
		/** @var PagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getParent($this);
	}


	/**
	 * Get all the parent page and all of its parents pages of the current entity
	 *
	 * @noinspection PhpUnused
	 */
	public function getParents (array $aa_options = [], int $ai_currentLevel = 0): ?CollectionInterface {
		/** @var PagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($this->getSource());

		return $lo_table->getParents($this, $aa_options, $ai_currentLevel);
	}


	/**
	 * Make sure the slug is always lowercase, dashed and free of special characters
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setSlug (string $as_slug): string {
		$ls_slug = Text::slug($as_slug, ['preserve' => '/']);
		$ls_slug = trim($ls_slug, '/');

		if (str_contains($ls_slug, '/')) {
			$ls_slug = substr($ls_slug, strrpos($ls_slug, '/') + 1);
		}

		return mb_strtolower($ls_slug);
	}
}
