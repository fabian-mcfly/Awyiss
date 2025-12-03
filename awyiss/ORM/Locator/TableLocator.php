<?php declare(strict_types=1);


namespace Awyiss\ORM\Locator;


use Awyiss\Core\App;
use Awyiss\Event\EventListenersProvider;
use Awyiss\Model\Table;
use Awyiss\ORM\AssociationCollection;
use Cake\ORM\Locator\TableLocator as BaseTableLocator;
use Cake\ORM\Table as BaseTable;


/**
 * @inheritDoc
 */
class TableLocator extends BaseTableLocator {
	/**
	 * Fallback class to use
	 *
	 * @var class-string<\Awyiss\Model\Table>
	 */
	protected string $fallbackClassName = Table::class;


	/**
	 * @inheritDoc
	 */
	protected function createInstance(string $alias, array $options): BaseTable {
		EventListenersProvider::loadListener($alias, 'Global');

		if (empty($options['associations'])) {
			$associations = new AssociationCollection($this);
			$options['associations'] = $associations;
		}

		return parent::createInstance($alias, $options);
	}


	/**
	 * Returns an array of all the instances
	 * created by this locator.
	 *
	 * @return array<string, \Cake\ORM\Table>
	 */
	public function getInstances(): array {
		return $this->instances;
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
			$options['className'] = $alias;
		}

		if (str_contains($options['className'], '\\') && class_exists($options['className'])) {
			return $options['className'];
		}

		foreach ($this->locations as $location) {
			$className = App::className($options['className'], $location, 'Table');
			if ($className !== null) {
				return $className;
			}
		}


		return null;
	}
}
