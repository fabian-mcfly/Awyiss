<?php declare(strict_types=1);


namespace Awyiss\Model\Trait;


use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Utility\Inflector;
use Cake\Datasource\FactoryLocator;


/**
 * Provide methods for the page role enum
 */
trait PageRoleEnumTrait {
	/**
	 * @inheritDoc
	 */
	public static function tryFromName(string $name): ?PageRoleEnumInterface {
		$pageRoles = static::cases();
		$name = Inflector::camelize(Inflector::singularize($name));

		$offset = array_search($name, array_column($pageRoles, 'name'));
		if ($offset === false) {
			return null;
		}

		return $pageRoles[ $offset ];
	}


	/**
	 * @inheritDoc
	 */
	public function label(): string {
		$headline = __d($this->name, 'headline_overview');
		if (!str_contains($headline, '::')) {
			return $headline;
		}

		$pageRoles = $this->fetchPageRoles();
		if (isset($pageRoles[ Inflector::underscore($this->name) ])) {
			return $pageRoles[ Inflector::underscore($this->name) ]->label;
		}

		return Inflector::humanize(Inflector::underscore($this->name));
	}


	/**
	 * @inheritDoc
	 */
	public function tableAlias(): string {
		return Inflector::camelize(Inflector::pluralize($this->name));
	}


	/**
	 * @inheritDoc
	 */
	public function tableName(): string {
		return Inflector::underscore(Inflector::pluralize($this->name));
	}


	/**
	 * @return array<\Awyiss\Model\Entity\PageRole>
	 */
	protected function fetchPageRoles(): array {
		static $pageRoles;

		if (isset($pageRoles)) {
			return $pageRoles;
		}

		$tableLocator = FactoryLocator::get('Table');

		/** @var array<\Awyiss\Model\Entity\PageRole> $pageRoles */
		$pageRoles = $tableLocator
			->get('PageRoles')
			->find()
			->all()
			->indexBy('identifier')
			->toArray()
		;

		return $pageRoles;
	}
}
