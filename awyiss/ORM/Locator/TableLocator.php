<?php declare(strict_types=1);


namespace Awyiss\ORM\Locator;


use Awyiss\Core\App;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Model\Entity\Language;
use Awyiss\Model\Table;
use Cake\ORM\Locator\TableLocator as BaseTableLocator;
use Cake\ORM\Table as BaseTable;


/**
 * @inheritDoc
 */
class TableLocator extends BaseTableLocator {
	/**
	 * Fallback class to use
	 *
	 * @var class-string<Table>
	 */
	protected string $fallbackClassName = Table::class;
	protected ?Language $translateLanguage = NULL;
	/*
	 * {@inheritDoc}
	 *
	 * ---
	 *
	 * This variation might return a class that extends either
	 *        \<CUSTOM_NAMESPACE>\Model\Table\PagesTable
	 * or
	 *        \Awyiss\Model\Table\PagesTable
	 * in case no matching table was found and the alias is a known pagerole (constant "PAGEROLE_<ALIAS>" exists).
	 *
	 * @param string               $as_alias   The alias name you want to get. Should be in CamelCase format.
	 * @param array<string, mixed> $aa_options The options you want to build the table with.
	 *                                         If a table has already been loaded the options will be ignored.
	 *
	 * @return BaseTable
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @throws \Exception
	 */
	/*public function get (string $as_alias, array $aa_options = []): BaseTable {
		try {
			$lo_table = parent::get($as_alias, $aa_options);
		}
		catch (MissingTableClassException $ex) {
			$ls_alias = ($aa_options['forPageRole'] ?? NULL) ? $aa_options['forPageRole'] : $as_alias;
			$ls_singular = Inflector::singularize(Inflector::underscore($ls_alias));

			$ls_constant = 'PAGEROLE_' . strtoupper($ls_singular);
			if (!defined($ls_constant)) {
				throw $ex;
			}

			$ls_alias = Inflector::camelize(Inflector::tableize($as_alias));
			#@var PagesTable $lo_table
			$lo_table = parent::get($ls_alias, [
					'className' => App::className('PagesTable', 'Model/Table'),
					'forPageRole' => $ls_singular,
				] + $aa_options);
			//$lo_table->setPageRole($ls_singular, constant($ls_constant));
			$lo_table->initializeAttributable($ls_alias, 'page_id');

			if ($lo_table->hasBehavior('Authorization')) {
				#@noinspection PhpPossiblePolymorphicInvocationInspection
				$lo_table->getBehavior('Authorization')->setScope($ls_alias);
			}
		}

		return $lo_table;
	}*/


	/**
	 * @return null|Language
	 */
	public function getTranslateLanguage(): ?Language {
		return $this->translateLanguage;
	}


	/**
	 * @param null|Language $ao_language
	 *
	 * @return TableLocator
	 */
	public function setTranslateLanguage(?Language $ao_language): static {
		$this->translateLanguage = $ao_language;


		return $this;
	}


	/**
	 * @inheritDoc
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @throws \ReflectionException
	 */
	protected function createInstance(string $as_alias, array $aa_options): BaseTable {
		EventListenersProvider::loadListener($as_alias, 'Global');


		/*if (Awyiss::getRealm()) {
		}*/


		return parent::createInstance($as_alias, $aa_options + ['translateLanguage' => $this->getTranslateLanguage()]);
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
	 *
	 * @return string|null
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _getClassName(string $as_alias, array $aa_options = []): ?string {
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
