<?php declare(strict_types=1);


namespace Awyiss\ORM\Locator;


use Awyiss\Core\App;
use Awyiss\Model\Table;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Table as BaseTable;
use Cake\ORM\Locator\TableLocator as BaseTableLocator;
use Cake\Utility\Inflector;


/**
 * @inheritDoc
 */
class TableLocator extends BaseTableLocator {
	/**
	 * Fallback class to use
	 *
	 * @var class-string<\Awyiss\Model\Table>
	 */
	protected $fallbackClassName = Table::class;


	/**
	 * {@inheritDoc}
	 *
	 * ---
	 *
	 * This variation might return an anonymous class that extends either
	 * 		\<CUSTOM_NAMESPACE>\Model\Table\PagesTable
	 * or
	 * 		\Awyiss\Model\Table\PagesTable
	 * in case no matching table was found and the alias is a known pagerole (constant "PAGEROLE_<ALIAS>" exists).
	 *
	 * @param string $as_alias The alias name you want to get. Should be in CamelCase format.
	 * @param array<string, mixed> $aa_options The options you want to build the table with.
	 *   If a table has already been loaded the options will be ignored.
	 * @return \Cake\ORM\Table
	 * @throws \RuntimeException When you try to configure an alias that already exists.
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function get (string $as_alias, array $aa_options = []): BaseTable {
		try {
			$lo_table = parent::get($as_alias, $aa_options);
		}
		catch (MissingTableClassException $ex) {
			$ls_singular = Inflector::singularize($as_alias);
			$ls_alias = Inflector::pluralize($ls_singular);

			$ls_constant = 'PAGEROLE_' . strtoupper($ls_singular);
			if (defined($ls_constant)) {
				/** @var \Awyiss\Model\Table\PagesTable $lo_table */
				$lo_table = parent::get($ls_alias, [
						'className' => App::className('PagesTable', 'Model/Table'),
					] + $aa_options);
				$lo_table->setPageRoleId(constant($ls_constant));

				$ls_identifier = Inflector::pluralize($ls_singular);
				if ($lo_table->hasBehavior('Authorization')) {
					/** @noinspection PhpPossiblePolymorphicInvocationInspection */
					$lo_table->getBehavior('Authorization')->setScope($ls_identifier);
				}
			}
			else {
				throw $ex;
			}
		}

		return $lo_table;
	}


	/**
	 * {@inheritDoc}
	 *
	 * ---
	 *
	 * Reimplemented this method 1:1 from \Cake\ORM\Locator\TableLocator::_getClassName,
	 * so it'll use \Awyiss\Core\App::className to find the class
	 *
	 * @param string $as_alias The alias name you want to get. Should be in CamelCase format.
	 * @param array<string, mixed> $aa_options Table options array.
	 * @return string|null
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _getClassName (string $as_alias, array $aa_options = []): ?string {
		if (empty($aa_options['className'])) {
			$aa_options['className'] = $as_alias;
		}

		if (str_contains($aa_options['className'], '\\') && class_exists($aa_options['className'])) {
			return $aa_options['className'];
		}

		foreach ($this->locations as $ls_location) {
			$ls_className = App::className($aa_options['className'], $ls_location, 'Table');
			if ($ls_className !== NULL) {
				return $ls_className;
			}
		}

		return NULL;
	}
}