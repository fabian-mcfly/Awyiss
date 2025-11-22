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
		$la_texts = [];

		$la_fields = $fields ?: $this->getTranslatableFields($entity);

		foreach ($la_fields as $ls_field) {
			if ($entity->has($ls_field)) {
				$la_texts[ $ls_field ] = $entity->get($ls_field);
				continue;
			}

			if ($entity->has('attributes') && $entity->attributes instanceof EntityInterface) {
				if ($entity->attributes->has($ls_field)) {
					$la_texts[ $ls_field ] = $entity->attributes->get($ls_field);
				}
			}
		}

		$la_texts = array_filter($la_texts, fn($value) => !empty($value));
		if (empty($la_texts)) {
			// All fields are empty or no translatable fields found
			return $entity;
		}

		// Translate all texts in batch
		$la_results = $this->translateBatch($la_texts, $targetLanguage, $sourceLanguage, $options);

		if ($la_results === false) {
			return false;
		}

		// Update the entity with translated fields
		foreach ($la_results as $ls_field => $lo_translation) {
			if ($lo_translation->isSuccess()) {
				$entity->set($ls_field, $lo_translation->getTranslatedText());

				if (!$entity->has($ls_field) && $entity->has('attributes') && $entity->attributes instanceof EntityInterface) {
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

		/** @var \Awyiss\Model\Table\ContentsTable $lo_contentsTable */
		$lo_contentsTable = FactoryLocator::get('Table')->get('Contents');
		if (!$lo_contentsTable->hasAttributes()) {
			return static::$translationFieldsCache['content'];
		}

		$lo_attributes = $lo_contentsTable->getAttributes();
		foreach ($lo_attributes as $lo_attribute) {
			if (
				in_array($lo_attribute->inputType, [
					'text',
					'textarea',
					'texteditor',
				])
			) {
				static::$translationFieldsCache['content'][] = $lo_attribute->identifier;
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
		$ls_pageRole = $entity->pageRoleId->name;

		if (isset(static::$translationFieldsCache[ $ls_pageRole ])) {
			return static::$translationFieldsCache[ $ls_pageRole ];
		}

		static::$translationFieldsCache[ $ls_pageRole ] = ['title', 'meta_title', 'meta_description'];

		/** @var \Awyiss\Model\Table\PagesTable $lo_table */
		$lo_table = FactoryLocator::get('Table')->get(Inflector::pluralize($ls_pageRole));
		if (!$lo_table->hasAttributes()) {
			return static::$translationFieldsCache[ $ls_pageRole ];
		}

		$lo_attributes = $lo_table->getAttributes();
		foreach ($lo_attributes as $lo_attribute) {
			if (
				in_array($lo_attribute->inputType, [
					'text',
					'textarea',
					'texteditor',
				])
			) {
				static::$translationFieldsCache[ $ls_pageRole ][] = $lo_attribute->identifier;
			}
		}

		return static::$translationFieldsCache[ $ls_pageRole ];
	}
}
