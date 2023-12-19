<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


/**
 * Page Entity
 *
 * @property int $id
 * @property string|null $slug
 * @property string|null $languages_shortcode
 * @property string|null $title
 * @property string|null $redirect_link
 * @property \Cake\I18n\FrozenTime|null $eventdate_start
 * @property \Cake\I18n\FrozenTime|null $eventdate_end
 * @property \Cake\I18n\FrozenTime|null $publishdate_start
 * @property \Cake\I18n\FrozenTime|null $publishdate_end
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property bool $robots_index
 * @property bool $robots_follow
 * @property int $page_role_id
 * @property int $page_template_id
 * @property int|null $duplicate_of
 * @property int $system_order
 * @property bool $active
 * @property bool $parents_active
 * @property bool $deleted
 * @property int $parent_id
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created_on
 * @property int|null $changed_by
 * @property \Cake\I18n\FrozenTime|null $changed_on
 * @property int|null $deleted_by
 * @property \Cake\I18n\FrozenTime|null $deleted_on
 * @property \Awyiss\Model\Entity\Attribute $attributes
 * @property \Awyiss\Model\Entity\PageRole $page_role
 * @property \Awyiss\Model\Entity\PageTemplate $page_template
 * @property \Awyiss\Model\Entity\Page $parent_page
 * @property \Awyiss\Model\Entity\Page $duplicate
 * @property \Awyiss\Model\Entity\Page[] $children
 */
class Page extends \Awyiss\Model\Entity {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'slug' => TRUE,
		'languages_shortcode' => TRUE,
		'title' => TRUE,
		'redirect_link' => TRUE,
		'eventdate_start' => TRUE,
		'eventdate_end' => TRUE,
		'publishdate_start' => TRUE,
		'publishdate_end' => TRUE,
		'meta_title' => TRUE,
		'meta_description' => TRUE,
		'robots_index' => TRUE,
		'robots_follow' => TRUE,
		'page_role_id' => TRUE,
		'page_template_id' => TRUE,
		'duplicate_of' => TRUE,
		'system_order' => TRUE,
		'active' => TRUE,
		'parents_active' => TRUE,
		'parent_id' => TRUE,
		'attributes' => TRUE,
		'page_role' => TRUE,
		'page_template' => TRUE,
		'parent_page' => TRUE,
		'child_pages' => TRUE,
	];
	/**
	 * @inheritDoc
	 */
	protected array $defaults = [
		'page_role_id' => PAGEROLE_PAGE,
	];


	/**
	 * @noinspection PhpUnused
	 */
	protected function _setSlug (string $as_slug): string {
		$ls_slug = \Cake\Utility\Text::slug($as_slug);

		return mb_strtolower($ls_slug);
	}
}
