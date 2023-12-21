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
	protected ?Language $translateLanguage = null;


	/**
	 * @return Language|null
	 */
	public function getTranslateLanguage(): ?Language {
		return $this->translateLanguage;
	}


	/**
	 * @param Language|null $ao_language
	 * @return TableLocator
	 */
	public function setTranslateLanguage(?Language $ao_language): static {
		$this->translateLanguage = $ao_language;


		return $this;
	}


	/**
	 * @inheritDoc
	 * @throws \ReflectionException
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function createInstance(string $as_alias, array $aa_options): BaseTable {
		EventListenersProvider::loadListener($as_alias, 'Global');


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
	 * @return string|null
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
			if ($ls_className !== null) {
				return $ls_className;
			}
		}


		return null;
	}
}
