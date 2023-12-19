<?php


namespace Awyiss\ORM\Locator;


use Awyiss\Core\App;
use Awyiss\Model\Table;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Table as BaseTable;
use Cake\ORM\Locator\TableLocator as BaseTableLocator;


class TableLocator extends BaseTableLocator {
	/**
	 * Fallback class to use
	 *
	 * @var string
	 * @psalm-var class-string<\Awyiss\Model\Table>
	 */
	protected $fallbackClassName = Table::class;


	/**
	 * @inheritDoc
	 *
	 * This variation might return an anonymous class that extends either
	 *        \<CUSTOM_NAMESPACE>\Model\Table\PagesTable
	 * or
	 *        Awyiss\Model\Table\PagesTable
	 * in case no matching table was found and the alias is a known pagerole (constant "PAGEROLE_<alias>" exists).
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function get (string $as_alias, array $aa_options = []): BaseTable {
		try {
			$lo_table = parent::get($as_alias, $aa_options);
		}
		catch (MissingTableClassException $ex) {
			$ls_singular = \Cake\Utility\Inflector::singularize($as_alias);
			$ls_constant = 'PAGEROLE_' . strtoupper($ls_singular);
			if (defined($ls_constant)) {
				/** @var \Awyiss\Model\Table\PagesTable $lo_table */
				$lo_table = parent::get($as_alias, [
						'className' => App::className('PagesTable', 'Model/Table'),
					] + $aa_options);
				$lo_table->setPageRoleId(constant($ls_constant));
			}
			else {
				throw $ex;
			}
		}

		return $lo_table;
	}


	/**
	 * @inheritDoc
	 *
	 * Reimplemented this method 1:1 from \Cake\ORM\Locator\TableLocator::_getClassName,
	 * so it'll use \Awyiss\Core\App::className to find the class
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _getClassName (string $as_alias, array $aa_options = []): ?string {
		if (empty($aa_options['className'])) {
			$aa_options['className'] = $as_alias;
		}

		if (strpos($aa_options['className'], '\\') !== FALSE && class_exists($aa_options['className'])) {
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