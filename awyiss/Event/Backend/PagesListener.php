<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Core\App;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\Page;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;


/**
 * Event listeners for the Pages (and dynamically created page roles) scope of the backend
 */
class PagesListener implements EventListenerInterface {
	use EventListenerTrait;
	use IdentityAwareTrait;


	/**
	 * @var string
	 */
	protected static string $scope;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		$la_events = [];

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');
		foreach ($ls_pageRoleEnum::cases() as $le_pageRole) {
			$ls_identifier = Inflector::camelize(Inflector::pluralize($le_pageRole->name));

			$la_events += [
				'Model.' . $ls_identifier . '.beforeFind' => 'beforeFind',
				'Model.' . $ls_identifier . '.beforeSave' => 'beforeSave',
				'Model.' . $ls_identifier . '.afterSave' => 'afterSave',
				'Model.' . $ls_identifier . '.beforeSoftDelete' => 'beforeSoftDelete',
				'Model.' . $ls_identifier . '.beforeDelete' => 'beforeDelete',
				'Model.' . $ls_identifier . '.afterSoftDelete' => 'afterSoftDelete',
				'Model.' . $ls_identifier . '.afterDelete' => 'afterDelete',
			];
		}


		return $la_events;
	}

	/**
	 * Add a where-condition that limits all results to the page role set for this model
	 *
	 * @param Event $ao_event
	 * @param \Cake\ORM\Query\SelectQuery $ao_query
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind(Event $ao_event, SelectQuery $ao_query, ArrayObject $ao_options): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		if (!($ao_options['skipPageRoleCheck'] ?? false)) {
			$ao_query->where(['page_role_id' => $lo_table->getPageRole()]);
		}
	}


	/**
	 * Before saving a page, make sure its slug is unique.
	 *
	 * @param Event $ao_event
	 * @param \Awyiss\Model\Entity\Page $ao_entity
	 * @param ArrayObject $ao_options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $ao_event, Page $ao_entity, ArrayObject $ao_options): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$ls_field = $lo_table->getSchema()->getColumn('slug');
		$li_length = $ls_field ? $ls_field['length'] : 0;

		if (empty($ao_entity->slug)) {
			//Make sure the slug is set. Use the title if it's empty.
			$ao_entity->set('slug', $ao_entity->title);
		}

		if (
			!$ao_entity->isDirty('slug') && !$ao_entity->isDirty('languageShortcode') && !$ao_entity->isDirty('parentId')
		) {
			//If neither the slug, the language nor the parent id have changed, skip the slug logic
			return;
		}

		$ls_preSlug = '';
		if (!empty($ao_entity->parentId)) {
			/** @var \Awyiss\Model\Entity\Page $lo_parentPage */
			$lo_parentPage = $lo_table->get($ao_entity->parentId, skipPageRoleCheck: true);
			//If there's a parent page, add its slug the one of the current page
			$ls_preSlug = trim($lo_parentPage->slug, '/') . '/';
		}

		$la_parts = explode('/', $ao_entity->slug);
		$ls_slug = end($la_parts);
		$ls_slug = $ls_preSlug . $ls_slug;

		$ls_originalSlug = $ao_entity->hasOriginal('slug') ? $ao_entity->getOriginal('slug') : null;
		//When the slug has changed
		if ($ls_slug != $ls_originalSlug) {
			$ls_field = $lo_table->getAlias() . '.slug';

			$la_conditions = [
				$ls_field => $ls_slug,
				'language_shortcode' => $ao_entity->languageShortcode,
			];

			$ls_primaryKey = $lo_table->getPrimaryKey();
			$li_id = $ao_entity->get($ls_primaryKey);
			if ($li_id) {
				$la_conditions['NOT'] = [$lo_table->getAlias() . '.' . $ls_primaryKey => $li_id];
			}

			/**
			 * `$la_conditions` holds an array of query conditions that are used to find pages with the same
			 * slug
			 * ```
			 * [
			 *    "Pages.slug" => "new/slug/of/the/current/page"
			 *    "language_shortcode" => "de"
			 *    "NOT" => [
			 *        "Pages.id" => 1234
			 *    ]
			 * ]
			 * ```
			 */

			$li_i = 1;
			$ls_suffix = '';

			//As long as a page with the same slug exists, append an increasing number to the slug and try again
			while ($lo_table->exists($la_conditions, ['skipPageRoleCheck' => true])) {
				$li_i++;
				$ls_suffix = '-' . $li_i;

				if ($li_length && (mb_strlen($ls_slug . $ls_suffix) > $li_length)) {
					$ls_slug = mb_substr($ls_slug, 0, $li_length - mb_strlen($ls_suffix));
				}

				$la_conditions[ $ls_field ] = $ls_slug . $ls_suffix;
			}

			//Append the suffix, if it's not empty
			if ($ls_suffix) {
				$ls_slug .= $ls_suffix;
			}
		}

		$ao_entity->set('slug', $ls_slug, ['setter' => false]);
		if (!$ao_entity->isNew() && $ls_slug === $ls_originalSlug) {
			$ao_entity->setDirty('slug', false);
		}
	}


	/**
	 * @param Event $ao_event
	 * @param \Awyiss\Model\Entity\Page $ao_entity
	 * @param ArrayObject $ao_options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(Event $ao_event, Page $ao_entity, ArrayObject $ao_options): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$ls_originalSlug = $ao_entity->hasOriginal('slug') ? $ao_entity->getOriginal('slug') : null;
		if ($ls_originalSlug && $ao_entity->slug != $ls_originalSlug) {
			$lo_records = $lo_table->find('all', skipPageRoleCheck: true)->where(function (QueryExpression $ao_expression) use ($ls_originalSlug) {
				return $ao_expression->like('slug', $ls_originalSlug . '/%');
			})->all();

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$li_userId = $this->getIdentity()?->id;
			$ld_now = new DateTime('now');

			$lo_query = $lo_table->SlugHistory->insertQuery()->insert(['slug', 'page_id', 'created_by', 'created_on']);
			$lo_query->values([
				'slug' => $ao_entity->languageShortcode . '/' . $ls_originalSlug,
				'page_id' => $ao_entity->id,
				'created_by' => $li_userId,
				'created_on' => $ld_now,
			]);
			/** @var \Awyiss\Model\Entity\Page $lo_page */
			foreach ($lo_records as $lo_page) {
				$lo_query->values([
					'slug' => $lo_page->languageShortcode . '/' . $lo_page->slug,
					'page_id' => $lo_page->id,
					'created_by' => $li_userId,
					'created_on' => $ld_now,
				]);
			}
			$lo_query->execute();


			$lo_query = $lo_table->updateQuery();

			/**
			 * UPDATE pages SET slug = (CONCAT('newslug', substr(slug, '8'))) WHERE slug LIKE 'oldslug/%'
			 *
			 * @noinspection PhpUndefinedMethodInspection
			 */
			$lo_query->update($lo_table->getTable())->set('slug', $lo_query->newExpr($lo_query->func()->concat([
				$ao_entity->slug,
				$lo_query->func()->substr([
					'slug' => 'identifier',
					mb_strlen($ls_originalSlug) + 1,
				], [
					null,
					'integer',
				]),
			])))->where(function (QueryExpression $ao_expression/*, Query $ao_query*/) use ($ls_originalSlug) {
				return $ao_expression->like('slug', $ls_originalSlug . '/%');
			})->execute();
		}
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @return void
	 * @throws \Exception
	 */
	public function beforeSoftDelete(Event $ao_event): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$lo_table->Contents->disableCascadeCallbacks();
		$lo_table->Contents->forPageRole($lo_table->getPageRole(), false);
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @return void
	 * @throws \Exception
	 */
	public function beforeDelete(Event $ao_event): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$lo_table->Contents->disableCascadeCallbacks();
		$lo_table->Contents->forPageRole($lo_table->getPageRole(), false);
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @return void
	 */
	public function afterSoftDelete(Event $ao_event): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$lo_table->Contents->enableCascadeCallbacks();
	}


	/**
	 * @param \Cake\Event\Event $ao_event
	 * @return void
	 */
	public function afterDelete(Event $ao_event): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $ao_event->getSubject();

		$lo_table->Contents->enableCascadeCallbacks();
	}
}
