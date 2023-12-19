<?php declare(strict_types=1);


namespace FoobarCustomer\Model\Entity;


use Awyiss\Model\Entity\Page;


/**
 * Publication Entity
 *
 * @property int $id
 * @property int $pageRoleId
 * @property int $pageTemplateId
 * @property int|null $parentId
 * @property string $languageShortcode
 * @property string $slug
 * @property string $title
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
 */
class Publication extends Page {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'pageRoleId' => true,
		'pageTemplateId' => true,
		'parentId' => true,
		'languageShortcode' => true,
		'slug' => true,
		'title' => true,
		'redirectLink' => true,
		'metaTitle' => true,
		'metaDescription' => true,
		'robotsIndex' => true,
		'robotsFollow' => true,
		'duplicateOf' => true,
		'systemOrder' => true,
		'active' => true,
		'parentsActive' => true,
		'deleted' => true,
		'createdBy' => true,
		'createdOn' => true,
		'changedBy' => true,
		'changedOn' => true,
		'deletedBy' => true,
		'deletedOn' => true,
		'duplicatePublications' => true,
		'duplicateOfPublication' => true,
		'childPublications' => true,
		'parentPublication' => true,
		'contents' => true,
		'language' => true,
		'pageRole' => true,
		'pageTemplate' => true,
	];

	/**
	* @inheritDoc
	*/
	protected static array $fieldMap = [
		'page_role_id' => 'pageRoleId',
		'page_template_id' => 'pageTemplateId',
		'parent_id' => 'parentId',
		'language_shortcode' => 'languageShortcode',
		'redirect_link' => 'redirectLink',
		'meta_title' => 'metaTitle',
		'meta_description' => 'metaDescription',
		'robots_index' => 'robotsIndex',
		'robots_follow' => 'robotsFollow',
		'duplicate_of' => 'duplicateOf',
		'system_order' => 'systemOrder',
		'parents_active' => 'parentsActive',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'duplicate_publications' => 'duplicatePublications',
		'duplicate_of_publication' => 'duplicateOfPublication',
		'child_publications' => 'childPublications',
		'parent_publication' => 'parentPublication',
		'page_role' => 'pageRole',
		'page_template' => 'pageTemplate',
	];
	/**
	* @inheritDoc
	*/
	protected array $defaults = [
		'pageRoleId' => PAGEROLE_PUBLICATION,
	];}
