<?php declare(strict_types=1);


namespace Awyiss\Model\Trait;


use Awyiss\Model\Enum\PageRoleEnumInterface;
use Cake\Utility\Inflector;


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
}
