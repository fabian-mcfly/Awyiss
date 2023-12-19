<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * Page Entity
 *
 * @property int $id
 * @property string|NULL $slug
 * @property string|NULL $language_shortcode
 * @property string|NULL $title
 * @property string|NULL $redirect_link
 * @property \Cake\I18n\FrozenTime|NULL $eventdate_start
 * @property \Cake\I18n\FrozenTime|NULL $eventdate_end
 * @property \Cake\I18n\FrozenTime|NULL $publishdate_start
 * @property \Cake\I18n\FrozenTime|NULL $publishdate_end
 * @property string|NULL $meta_title
 * @property string|NULL $meta_description
 * @property bool $robots_index
 * @property bool $robots_follow
 * @property int $page_role_id
 * @property int $page_template_id
 * @property int|NULL $duplicate_of
 * @property int $system_order
 * @property bool $active
 * @property bool $parents_active
 * @property bool $deleted
 * @property int $parent_id
 * @property int|NULL $created_by
 * @property \Cake\I18n\FrozenTime|NULL $created_on
 * @property int|NULL $changed_by
 * @property \Cake\I18n\FrozenTime|NULL $changed_on
 * @property int|NULL $deleted_by
 * @property \Cake\I18n\FrozenTime|NULL $deleted_on
 * @property \Awyiss\Model\Entity\Attribute $attributes
 * @property \Awyiss\Model\Entity\PageRole $page_role
 * @property \Awyiss\Model\Entity\PageTemplate $page_template
 * @property \Awyiss\Model\Entity\Page $parent_page
 * @property \Awyiss\Model\Entity\Page $duplicate
 * @property \Awyiss\Model\Entity\Page[] $children
 */
class Page extends Entity {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'slug' => TRUE,
		'language_shortcode' => TRUE,
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
