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
		$la_pageRoles = static::cases();
		$ls_name = Inflector::camelize(Inflector::singularize($name));

		$li_offset = array_search($ls_name, array_column($la_pageRoles, 'name'));
		if ($li_offset === false) {
			return null;
		}

		return $la_pageRoles[ $li_offset ];
	}


	/**
	 * @inheritDoc
	 */
	public function label(): string {
		$ls_headline = __d($this->name, 'headline_overview');
		if (!str_contains($ls_headline, '::')) {
			return $ls_headline;
		}

		$la_pageRoles = $this->fetchPageRoles();
		if (isset($la_pageRoles[ Inflector::underscore($this->name) ])) {
			return $la_pageRoles[ Inflector::underscore($this->name) ]->label;
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
		static $la_pageRoles;

		if (isset($la_pageRoles)) {
			return $la_pageRoles;
		}

		$lo_tableLocator = FactoryLocator::get('Table');

		/** @var array<\Awyiss\Model\Entity\PageRole> $la_pageRoles */
		$la_pageRoles = $lo_tableLocator->get('PageRoles')->find()->all()->indexBy('identifier')->toArray();

		return $la_pageRoles;
	}
}
