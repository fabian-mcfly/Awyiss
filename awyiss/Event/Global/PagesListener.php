<?php

/**
 * @noinspection PhpInternalEntityUsedInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Event\Global;


use ArrayObject;
use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Core\App;
use Awyiss\Utility\Inflector;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\MysqlSchemaDialect;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;


/**
 * Event listeners for the Pages (and dynamically created page roles) scope
 */
class PagesListener implements EventListenerInterface {
	use IdentityAwareTrait;
	use LocatorAwareTrait;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		$events = [];

		/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
		$pageRoleEnum = App::className('PageRole', 'Model/Enum');
		foreach ($pageRoleEnum::cases() as $pageRole) {
			$identifier = Inflector::camelize(Inflector::pluralize($pageRole->name));

			$events += [
				'Model.' . $identifier . '.beforeFind' => 'beforeFind',
			];
		}

		return $events;
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
		/** @var \Awyiss\Model\Table\PagesTable $pagesTable */
		$pagesTable = $event->getSubject();

		if (($options['skipPageRoleCheck'] ?? false) !== true) {
			$query->where(['pageRoleId' => $pagesTable->getPageRole()]);

			return;
		}

		$pageRoles = $this
			->fetchTable('PageRoles')
			->findAllAndCache()
			->indexBy('identifier')
			->toArray()
		;

		$prefixedColumn = $query->getRepository()->getAlias() . '.pageRoleId';

		$dialect = $query
			->getConnection()
			->getDriver()
			->schemaDialect()
		;

		// Only MySQL supports FIND_IN_SET for ordering.
		if ($dialect instanceof MysqlSchemaDialect) {
			/** @noinspection PhpUndefinedMethodInspection */
			$query->orderByAsc($query->expr($query
				->func()
				->FIND_IN_SET([
					$prefixedColumn => 'identifier',
					implode(',', array_column($pageRoles, 'id')),
				])));

			return;
		}

		$query->orderBy(function (QueryExpression $exp) use ($pageRoles, $prefixedColumn) {
			$index = 0;

			$case = $exp->case();
			foreach ($pageRoles as $pageRole) {
				$case->when([$prefixedColumn => $pageRole->id])->then($index, 'integer');

				$index++;
			}

			$case->else(999, 'integer');

			return $case;
		});
	}
}
