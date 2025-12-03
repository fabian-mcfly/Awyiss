<?php declare(strict_types=1);


namespace Awyiss\Utility\Translation;


use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Awyiss\Utility\Inflector;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;


/**
 * Abstract base class for translation services
 * Provides common functionality that can be shared across different translation service implementations.
 */
abstract class AbstractTranslationService implements TranslationServiceInterface {
	/**
	 * @var int
	 */
	protected int $batchSize = 10;
	/**
	 * @var array Cache of translatable fields per entity type
	 */
	protected static array $translationFieldsCache = [];


	/**
	 * @inheritDoc
	 */
	public function translateEntity(
		EntityInterface $entity,
		string $targetLanguage,
		?string $sourceLanguage = null,
		array $fields = [],
		array $options = []
	): EntityInterface|false {
		// Extract texts from entity fields
		$texts = [];

		$fields = $fields ?: $this->getTranslatableFields($entity);

		foreach ($fields as $field) {
			if ($entity->has($field)) {
				$texts[ $field ] = $entity->get($field);
				continue;
			}

			if ($entity->has('attributes') && $entity->attributes instanceof EntityInterface) {
				if ($entity->attributes->has($field)) {
					$texts[ $field ] = $entity->attributes->get($field);
				}
			}
		}

		$texts = array_filter($texts, fn($value) => !empty($value));
		if (empty($texts)) {
			// All fields are empty or no translatable fields found
			return $entity;
		}

		// Translate all texts in batch
		$results = $this->translateBatch($texts, $targetLanguage, $sourceLanguage, $options);

		if ($results === false) {
			return false;
		}

		// Update the entity with translated fields
		foreach ($results as $field => $translation) {
			if ($translation->isSuccess()) {
				$entity->set($field, $translation->getTranslatedText());

				if (!$entity->has($field) && $entity->has('attributes') && $entity->attributes instanceof EntityInterface) {
					// Field is an attribute
					$entity->setDirty('attributes');
				}
			}
		}

		return $entity;
	}


	/**
	 * @inheritDoc
	 */
	public function getBatchSize(): int {
		return $this->batchSize;
	}


	/**
	 * Get the list of translatable fields for an entity
	 *
	 * @param EntityInterface $entity The entity to inspect
	 * @return array Array of field names that are translatable
	 */
	protected function getTranslatableFields(EntityInterface $entity): array {
		if ($entity instanceof Content) {
			return $this->getContentTranslatableFields();
		}

		if ($entity instanceof Page) {
			return $this->getPageRoleTranslatableFields($entity);
		}

		return [];
	}


	/**
	 * Get the list of translatable fields for Content entities,
	 * including dynamic attributes if available.
	 *
	 * @return array
	 */
	protected function getContentTranslatableFields(): array {
		if (isset(static::$translationFieldsCache['content'])) {
			return static::$translationFieldsCache['content'];
		}

		static::$translationFieldsCache['content'] = ['title', 'subtitle', 'text'];

		/** @var \Awyiss\Model\Table\ContentsTable $contentsTable */
		$contentsTable = FactoryLocator::get('Table')->get('Contents');
		if (!$contentsTable->hasAttributes()) {
			return static::$translationFieldsCache['content'];
		}

		$attributes = $contentsTable->getAttributes();
		foreach ($attributes as $attribute) {
			if (
				in_array($attribute->inputType, [
					'text',
					'textarea',
					'texteditor',
				])
			) {
				static::$translationFieldsCache['content'][] = $attribute->identifier;
			}
		}

		return static::$translationFieldsCache['content'];
	}


	/**
	 * Get the list of translatable fields for PageRole entities
	 *
	 * @param \Awyiss\Model\Entity\Page $entity
	 * @return array
	 */
	protected function getPageRoleTranslatableFields(Page $entity): array {
		$pageRole = $entity->pageRoleId->name;

		if (isset(static::$translationFieldsCache[ $pageRole ])) {
			return static::$translationFieldsCache[ $pageRole ];
		}

		static::$translationFieldsCache[ $pageRole ] = ['title', 'meta_title', 'meta_description'];

		/** @var \Awyiss\Model\Table\PagesTable $table */
		$table = FactoryLocator::get('Table')->get(Inflector::pluralize($pageRole));
		if (!$table->hasAttributes()) {
			return static::$translationFieldsCache[ $pageRole ];
		}

		$attributes = $table->getAttributes();
		foreach ($attributes as $attribute) {
			if (
				in_array($attribute->inputType, [
					'text',
					'textarea',
					'texteditor',
				])
			) {
				static::$translationFieldsCache[ $pageRole ][] = $attribute->identifier;
			}
		}

		return static::$translationFieldsCache[ $pageRole ];
	}
}
