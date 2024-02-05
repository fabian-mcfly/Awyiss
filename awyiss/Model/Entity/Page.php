<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Core\App;
use Awyiss\Database\Type\PageRoleEnumInterface;
use Awyiss\Model\Entity;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Text;


/**
 * Page Entity
 *
 * @property int $id
 * @property \Awyiss\Database\Type\PageRoleEnumInterface|null $pageRoleId
 * @property int|null $pageTemplateId
 * @property int|null $parentId
 * @property string|null $languageShortcode
 * @property string|null $slug
 * @property string|null $title
 * @property string|null $redirectLink
 * @property string|null $metaTitle
 * @property string|null $metaDescription
 * @property bool $robotsIndex
 * @property bool $robotsFollow
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
 * @property \Awyiss\Model\Entity\PageRole $pageRole
 * @property \Awyiss\Model\Entity\PageTemplate $pageTemplate
 * @property \Awyiss\Model\Entity\Page[] $duplicatingPages
 * @property \Awyiss\Model\Entity\Page $duplicateOfPage
 * @property \Awyiss\Model\Entity\Page $parentPage
 * @property \Awyiss\Model\Entity\Page[] $childPages
 * @property \Awyiss\Model\Entity\Content[] $contents
 * @property \Awyiss\Model\Entity\Language $language
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
		'duplicating_pages' => 'duplicatingPages',
		'duplicate_of_page' => 'duplicateOfPage',
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
	 * @param mixed $ax_pageRoleId
	 * @return \Awyiss\Database\Type\PageRoleEnumInterface|int|null
	 */
	protected function _setPageRoleId(mixed $ax_pageRoleId): PageRoleEnumInterface|int|null {
		if (is_string($ax_pageRoleId)) {
			return (int)$ax_pageRoleId;
		}


		return $ax_pageRoleId;
	}


	/**
	 * Make sure the slug is always lowercase, dashed and free of special characters
	 *
	 * @param string|null $as_slug
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Page::$slug
	 */
	protected function _setSlug(?string $as_slug): ?string {
		if ($as_slug === null) {
			return null;
		}

		$ls_slug = Text::slug($as_slug, ['preserve' => '/']);
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
		/** @var class-string<\Awyiss\Database\Type\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		$la_parts = explode('\\', static::class);

		return parent::defaultValues() + [
			'pageRoleId' => $ls_pageRoleEnum::tryFromName(end($la_parts)),
		];
	}
}
