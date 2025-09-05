<?php

/**
 * @noinspection PhpInternalEntityUsedInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Event\Global;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Core\App;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Utility\Inflector;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\SqliteSchemaDialect;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;


/**
 * Event listeners for the Pages (and dynamically created page roles) scope
 */
class PagesListener implements EventListenerInterface {
	use EventListenerTrait;
	use IdentityAwareTrait;
	use LocatorAwareTrait;


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
			];
		}

		return $la_events;
	}

	/**
	 * Add a where-condition that limits all results to the page role set for this model
	 *
	 * @param Event $event
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeFind(Event $event, SelectQuery $query, ArrayObject $options): void {
		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = $event->getSubject();

		if (($options['skipPageRoleCheck'] ?? false) !== true) {
			$query->where(['page_role_id' => $lo_table->getPageRole()]);
			return;
		}

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $ls_pageRoleEnum */
		$ls_pageRoleEnum = App::className('PageRole', 'Model/Enum');

		$ls_prefixedColumn = $query->getRepository()->getAlias() . '.page_role_id';

		$lo_dialect = $query->getConnection()->getDriver()->schemaDialect();
		/**
		 * SQLite does not support FIND_IN_SET(),
		 * so ordering using CASE WHEN is used instead
		 */
		if ($lo_dialect instanceof SqliteSchemaDialect) {
			$query->orderBy(function (QueryExpression $exp) use ($ls_pageRoleEnum, $ls_prefixedColumn) {
				$li_index = 0;

				$lo_case = $exp->case();
				foreach ($ls_pageRoleEnum::cases() as $le_pageRole) {
					$lo_case->when([$ls_prefixedColumn => $le_pageRole->value])->then($li_index, 'integer');

					$li_index++;
				}

				$lo_case->else(999, 'integer');

				return $lo_case;
			});

			return;
		}

		/** @noinspection PhpUndefinedMethodInspection */
		$query->orderByAsc($query->newExpr($query->func()->FIND_IN_SET([
			$ls_prefixedColumn => 'identifier',
			implode(',', array_map(function (PageRoleEnumInterface $pageRole) {
				return $pageRole->value;
			}, $ls_pageRoleEnum::cases())),
		])));
	}
}
