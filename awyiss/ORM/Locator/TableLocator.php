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
	 * @param Language|null $language
	 * @return TableLocator
	 */
	public function setTranslateLanguage(?Language $language): static {
		$this->translateLanguage = $language;


		return $this;
	}


	/**
	 * @inheritDoc
	 * @throws \ReflectionException
	 */
	protected function createInstance(string $alias, array $options): BaseTable {
		EventListenersProvider::loadListener($alias, 'Global');


		return parent::createInstance($alias, $options + ['translateLanguage' => $this->getTranslateLanguage()]);
	}


	/**
	 * Reimplemented this method 1:1 from \Cake\ORM\Locator\TableLocator::_getClassName,
	 * so it'll use \Awyiss\Core\App::className to find the class
	 *
	 * @inheritDoc
	 * @param string $alias The alias name you want to get. Should be in CamelCase format.
	 * @param array<string, mixed> $options Table options array.
	 * @return string|null
	 */
	protected function _getClassName(string $alias, array $options = []): ?string {
		if (empty($options['className'])) {
			/** @noinspection PhpVariableNamingConventionInspection */
			$options['className'] = $alias;
		}

		if (str_contains($options['className'], '\\') && class_exists($options['className'])) {
			return $options['className'];
		}

		foreach ($this->locations as $ls_location) {
			$ls_className = App::className($options['className'], $ls_location, 'Table');
			if ($ls_className !== null) {
				return $ls_className;
			}
		}


		return null;
	}
}
