<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use ArrayIterator;
use Awyiss\Utility\Inflector;
use RuntimeException;


/**
 * A class to collect multiple ConfigOptions instances or nested ConfigOptionsCollection instances
 */
class ConfigOptionsCollection extends ArrayIterator {
	/**
	 * @var string
	 */
	protected string $identifier;


	/**
	 * Construct a new ConfigOptionsCollection
	 *
	 * @param string|null $identifier
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct(?string $identifier = null) {
		if ($identifier) {
			$this->identifier = ConfigOptionsProvider::sanitizeIdentifier($identifier);
		}
	}


	/**
	 * Adds a ConfigOption or a set of elements, containing nested ConfigOptions or ConfigOptionsCollection to this collection
	 *
	 * @param \Awyiss\Configuration\ConfigOption|array<int|string, \Awyiss\Configuration\ConfigOptionsCollection|\Awyiss\Configuration\ConfigOption|array> $configOption
	 * @return $this
	 */
	public function add(array|ConfigOption $configOption): static {
		/*
		 * If the provided value for `$configOption` is an instance of `ConfigOption`,
		 * add it to the current collection
		 */
		if ($configOption instanceof ConfigOption) {
			$identifier = $configOption->getIdentifier();

			//We cannot have the same identifier more than once inside this collection
			if ($this->offsetExists($identifier)) {
				throw new RuntimeException(sprintf('The identifier `%s` is already in use.', $identifier));
			}

			$this->offsetSet($identifier, $configOption);


			return $this;
		}

		//Traverse the provided array
		$configOptions = $configOption;
		foreach ($configOptions as $key => $configOption) {
			//If the key is a string, add a new sub-collection with that given identifier, containing everything in $lx_configOption
			if (is_string($key)) {
				$collection = new ConfigOptionsCollection($key);
				$collection->add($configOption);

				$this->addCollection($collection);

				continue;
			}

			//If the current value is an instance of ConfigOptionsCollection, add it as a new sub-collection
			if ($configOption instanceof ConfigOptionsCollection) {
				$this->addCollection($configOption);
			}
			//If the current value is an instance of ConfigOption, add it as is
			elseif ($configOption instanceof ConfigOption) {
				$this->add($configOption);
			}
			/*
			 * Otherwise the current value is most likely an array. So we try creating an instance of ConfigOption with it
			 * and then add that instance to the collection.
			 * */
			else {
				$this->add(new ConfigOption(...$configOption));
			}
		}


		return $this;
	}


	/**
	 * Adds the given ConfigOptionsCollection as a sub-collection to the current one.
	 * If the identifier of the ConfigOptionsCollection already exists in the current one, a `RuntimeException` is thrown.
	 *
	 * @param ConfigOptionsCollection $configOptionsCollection
	 * @return $this
	 * @throws RuntimeException
	 */
	public function addCollection(ConfigOptionsCollection $configOptionsCollection): static {
		$identifier = $configOptionsCollection->getIdentifier();

		if ($this->offsetExists($identifier)) {
			$offset = $this->offsetGet($identifier);
			if ($offset instanceof ConfigOptionsCollection) {
				foreach ($configOptionsCollection->getArrayCopy() as $configOptions) {
					if ($configOptions instanceof ConfigOptionsCollection) {
						$offset->addCollection($configOptions);
					}
					else {
						$offset->add($configOptions);
					}
				}


				return $this;
			}

			throw new RuntimeException(sprintf('The identifier `%s` is already in use.', $identifier));
		}

		$this->offsetSet($identifier, $configOptionsCollection);


		return $this;
	}


	/**
	 * @return string
	 */
	public function getIdentifier(): string {
		return $this->identifier;
	}


	/**
	 * Get all ConfigOptions from this collection and all nested collections
	 *
	 * All provided path parts will be concatenated with a dot to form the final key
	 *
	 * @param string ...$pathParts
	 * @return array
	 */
	public function getConfigOptions(string ...$pathParts): array {
		$configOptions = [];

		foreach ($this as $configOption) {
			if ($configOption instanceof ConfigOptionsCollection) {
				$configPathParts = $pathParts;
				$configPathParts[] = Inflector::variable($configOption->getIdentifier());
				$configOptions += $configOption->getConfigOptions(...$configPathParts);
			}
			else {
				$key = '';
				if (!empty($pathParts)) {
					$key .= implode('.', $pathParts) . '.';
				}
				$key .= Inflector::variable($configOption->getIdentifier());

				$configOptions[ $key ] = $configOption;
			}
		}


		return $configOptions;
	}


	/**
	 * @return array
	 */
	public function toArray(): array {
		$result = [];

		/** @noinspection PhpLoopCanBeConvertedToArrayMapInspection */
		foreach ($this as $key => $item) {
			$result[ $key ] = $item instanceof static ? $item->toArray() : $item;
		}


		return $result;
	}
}
