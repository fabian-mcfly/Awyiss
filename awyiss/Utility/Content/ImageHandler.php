<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


use Awyiss\Model\Entity;
use Awyiss\Model\Entity\MediaAssignment;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\MediaRenderOptions;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\View\View;
use Dom\Element;
use Dom\HTMLDocument;


/**
 * Class ImageHandler
 * This class is responsible for handling image tags in the content.
 *
 * It replaces the `<img>` tags with custom `<awyiss-responsive-image>` tags
 * when saving the content.
 *
 * It also rebuilds the `<img>` tags from the custom tags when loading the content
 * for the backend, or responsive picture tags for the frontend.
 *
 * This is necessary to ensure that images, inserted in the rich text editor,
 * are rendered with sources for different screen sizes.
 */
class ImageHandler {
	/**
	 * The default fields to check for `img`-tags
	 *
	 * @var array<int, string>
	 */
	protected static array $defaultFields = ['text', 'textHtml', 'successMessage'];


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $fields
	 * @param \Cake\Datasource\EntityInterface|null $referenceEntity
	 * @return void
	 */
	public static function replaceImageTags(EntityInterface $entity, array $fields = [], ?EntityInterface $referenceEntity = null): void {
		$fields = $fields ?: static::getDefaultFields($entity);

		foreach ($fields as $field) {
			if (!static::fieldIsValid($entity, $field)) {
				continue;
			}

			static::replaceImageTagsInField($entity, $field, null, $referenceEntity);
		}

		if ($entity->has('_translations')) {
			foreach ($entity->get('_translations') as $translation) {
				static::replaceImageTags($translation, $fields, $entity);
			}
		}
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $field
	 * @param string|null $value
	 * @param \Cake\Datasource\EntityInterface|null $referenceEntity
	 * @return string|null
	 */
	public static function replaceImageTagsInField(
		EntityInterface $entity,
		string $field,
		?string $value = null,
		?EntityInterface $referenceEntity = null
	): ?string {
		$value ??= $entity->get($field) ?? '';

		if (!is_string($value) || !str_contains($value, '<img')) {
			return $value;
		}

		$dom = static::getDom($value);

		// Find all <img> tags
		$tags = $dom->querySelectorAll('img');

		$foundSources = [];

		foreach ($tags as $tag) {
			// Get all attributes
			$attributes = [];
			foreach ($tag->attributes as $attribute) {
				$attributes[ Inflector::variable($attribute->name) ] = $attribute->value;
			}

			// Get the src attribute
			if ($attributes['src'] ?? null) {
				$foundSources[] = [
					'src' => $attributes['src'],
					'attributes' => $attributes,
					'node' => $tag,
				];
			}
		}

		/** @var \Awyiss\Model\Table $table */
		$table = FactoryLocator::get('Table')->get('Media');
		$media = $table->find('all')->where([
			'path IN' => array_map(function (array $foundSource) {
				return $foundSource['src'];
			}, $foundSources),
		])->all()->indexBy('path');

		if (!$media->count()) {
			return $value;
		}

		$media = $media->toArray();
		foreach ($foundSources as $source) {
			if (!isset($media[ $source['src'] ])) {
				continue;
			}

			$mediaEntity = $media[ $source['src'] ];

			// Create a new custom image tag
			$customTag = $dom->createElement('awyiss-responsive-image');

			$attributes = $source['attributes'];
			// Remove the source
			unset($attributes['src']);
			// Add the media id
			$attributes['mediaId'] = (string)$mediaEntity->id;

			// Set the JSON string as the content of the custom tag
			$customTag->textContent = json_encode($attributes);

			// Replace the original image tag with the custom tag
			$source['node']->parentNode->replaceChild($customTag, $source['node']);
		}

		// Build media assignments
		static::buildMediaAssignments($referenceEntity ?? $entity, $media);

		$entity->set($field, trim(static::getBody($dom)) ?: null);

		return $entity->get($field);
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $fields
	 * @param \Cake\Datasource\EntityInterface|null $referenceEntity
	 * @return void
	 */
	public static function rebuildSimpleImageTags(EntityInterface $entity, array $fields = [], ?EntityInterface $referenceEntity = null): void {
		$fields = $fields ?: static::getDefaultFields($entity);

		foreach ($fields as $field) {
			if (!static::fieldIsValid($entity, $field)) {
				continue;
			}

			static::rebuildSimpleImageTagsInField($entity, $field, null, $referenceEntity);
		}

		if ($entity->has('_translations')) {
			foreach ($entity->get('_translations') as $translation) {
				static::rebuildSimpleImageTags($translation, $fields, $entity);
			}
		}
	}


	/**
	 * Rebuilds the image tags in the given entity
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $field
	 * @param string|null $value
	 * @param \Cake\Datasource\EntityInterface|null $referenceEntity
	 * @return string|null
	 */
	public static function rebuildSimpleImageTagsInField(
		EntityInterface $entity,
		string $field,
		?string $value = null,
		?EntityInterface $referenceEntity = null
	): ?string {
		$value ??= $entity->get($field) ?? '';
		$isDirty = $entity->isDirty($field);

		if (!is_string($value) || !str_contains($value, '<awyiss-responsive-image')) {
			return $value;
		}

		$dom = static::getDom($value);

		// Find all <awyiss-responsive-image> tags
		$tags = $dom->querySelectorAll('awyiss-responsive-image');

		foreach ($tags as $tag) {
			[$attributes, $media] = self::extractMediaAttributes($dom, $tag, $referenceEntity ?? $entity);

			if (!$media) {
				continue;
			}

			// Create a new <img> tag
			$imgTag = $dom->createElement('img');
			$imgTag->setAttribute('src', $media->path);

			// Set the other attributes
			foreach ($attributes as $key => $attributeValue) {
				if ($key === 'mediaId') {
					continue;
				}

				$imgTag->setAttribute($key, (string)$attributeValue);
			}

			// Replace the custom tag with the <img> tag
			$tag->parentNode->replaceChild($imgTag, $tag);
		}

		$entity->set($field, trim(static::getBody($dom)) ?: null);
		$entity->setDirty($field, $isDirty);

		return $entity->get($field);
	}


	/**
	 * @param string|null $value
	 * @param array $media
	 * @param bool $absolutePath
	 * @return string|null
	 */
	public static function rebuildSimpleImageTagsInText(?string $value, array $media, bool $absolutePath = false): ?string {
		if (!is_string($value) || !str_contains($value, '<awyiss-responsive-image')) {
			return $value;
		}

		$dom = static::getDom($value);

		// Find all <awyiss-responsive-image> tags
		$tags = $dom->querySelectorAll('awyiss-responsive-image');

		$baseUrl = $absolutePath ? Router::url('/', true) : '';

		foreach ($tags as $tag) {
			$attributes = json_decode($tag->textContent, true);

			if (
				!is_array($attributes) ||
				!isset($attributes['mediaId']) ||
				!isset($media[ $attributes['mediaId'] ])
			) {
				continue;
			}

			// Create a new <img> tag
			$imgTag = $dom->createElement('img');
			$imgTag->setAttribute('src', $baseUrl . $media[ $attributes['mediaId'] ]->path);

			// Set the other attributes
			foreach ($attributes as $key => $attributeValue) {
				if ($key === 'mediaId') {
					continue;
				}

				$imgTag->setAttribute($key, (string)$attributeValue);
			}

			// Replace the custom tag with the <img> tag
			$tag->parentNode->replaceChild($imgTag, $tag);
		}

		return trim(static::getBody($dom)) ?: null;
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \Cake\View\View $view
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param array $fields
	 * @param \Cake\Datasource\EntityInterface|null $referenceEntity
	 * @return void
	 * @throws \Exception
	 */
	public static function replaceCustomImageTags(
		EntityInterface $entity,
		View $view,
		MediaRenderOptions $mediaRenderOptions,
		array $fields = [],
		?EntityInterface $referenceEntity = null
	): void {
		$fields = $fields ?: static::getDefaultFields($entity);

		foreach ($fields as $field) {
			if (!static::fieldIsValid($entity, $field)) {
				continue;
			}

			static::replaceCustomImageTagsInField($entity, $view, $mediaRenderOptions, $field, null, $referenceEntity);
		}

		if ($entity->has('_translations')) {
			foreach ($entity->get('_translations') as $translation) {
				static::replaceCustomImageTags($translation, $view, $mediaRenderOptions, $fields, $entity);
			}
		}
	}


	/**
	 * Rebuilds the image tags in the given entity
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \Cake\View\View $view
	 * @param \Awyiss\Utility\Media\MediaRenderOptions $mediaRenderOptions
	 * @param string $field
	 * @param string|null $value
	 * @param \Cake\Datasource\EntityInterface|null $referenceEntity
	 * @return string|null
	 * @throws \Exception
	 */
	public static function replaceCustomImageTagsInField(
		EntityInterface $entity,
		View $view,
		MediaRenderOptions $mediaRenderOptions,
		string $field,
		?string $value = null,
		?EntityInterface $referenceEntity = null
	): ?string {
		$value ??= $entity->get($field) ?? '';
		$isDirty = $entity->isDirty($field);

		if (!is_string($value) || !str_contains($value, '<awyiss-responsive-image')) {
			return $value;
		}

		/** @var \Awyiss\View\Helper\MediaHelper $mediaHelper */
		$mediaHelper = $view->helpers()->get('Media');

		$dom = static::getDom($value);

		// Find all <awyiss-responsive-image> tags
		$tags = $dom->querySelectorAll('awyiss-responsive-image');

		foreach ($tags as $tag) {
			[$attributes, $media] = self::extractMediaAttributes($dom, $tag, $referenceEntity ?? $entity);
			unset($attributes['mediaId']);

			if (!$media) {
				continue;
			}

			$with = [
				'attributes' => $attributes,
			];
			if ($attributes['width'] ?? null) {
				$with['width'] = (int)$attributes['width'];
				$with['responsive'] = false;
			}
			if ($attributes['height'] ?? null) {
				$with['height'] = (int)$attributes['height'];
				$with['responsive'] = false;
			}

			$htmlTag = $mediaHelper->htmlTag($media, $mediaRenderOptions->with($with));

			$value = str_replace($tag->ownerDocument->saveHTML($tag), $htmlTag, $value);
		}

		$entity->set($field, trim($value) ?: null);
		$entity->setDirty($field, $isDirty);

		return $entity->get($field);
	}


	/**
	 * @param string|null $text
	 * @return \Dom\HTMLDocument
	 */
	protected static function getDom(?string $text): HTMLDocument {
		return HTMLDocument::createFromString($text, LIBXML_NOERROR, 'UTF-8');
	}


	/**
	 * Returns the contents of `<body>`-tag of the given \Dom\HTMLDocument as a string
	 *
	 * @param \Dom\HTMLDocument $dom
	 * @return string|false
	 * @noinspection DuplicatedCode
	 */
	protected static function getBody(HTMLDocument $dom): string|false {
		$html = '';

		// Remove the opening and closing `<body>`-tags
		$body = $dom->querySelector('body');

		while ($body->firstChild) {
			$html .= $dom->saveHTML($body->firstChild);
			$body->removeChild($body->firstChild);
		}

		// Return the cleaned HTML
		return $html;
	}


	/**
	 * Builds the media assignments for the given entity
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array<string, \Awyiss\Model\Entity\Media> $media
	 * @return void
	 */
	protected static function buildMediaAssignments(EntityInterface $entity, array $media): void {
		$mediaAssignments = $entity->get('mediaAssignments') ?: [];
		$mediaAssignmentsTable = FactoryLocator::get('Table')->get('MediaAssignments');

		$originalMediaAssignments = $entity->hasOriginal('mediaAssignments') ? $entity->getOriginal('mediaAssignments') : [];
		$originalInlineAssignments = $originalMediaAssignments['inlineImgTag'] ?? [];

		// Remember the media ids
		$inlineAssignments = array_filter($mediaAssignments, function (MediaAssignment $assignment): bool {
			return $assignment->mediaElementSelectorIdentifier === 'inlineImgTag';
		});
		$mediaIdsFound = array_column($inlineAssignments, 'mediaId');
		foreach ($media as $mediaItem) {
			// If the media id is already in the assignments, skip it
			// There's no need to create multiple assignments for the same media
			if (in_array($mediaItem->id, $mediaIdsFound)) {
				continue;
			}

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$assignment = $mediaAssignmentsTable->newDefaultEntity();

			$assignment->patch([
				'mediaElementId' => 5, // 5 for `inlineImgTag`
				'mediaElementSelectorIdentifier' => 'inlineImgTag',
				'mediaId' => $mediaItem->id,
				'scope' => Inflector::underscore($entity->getSource()),
			]);

			// Copy the original assignment ID if it exists, but only if the entity is not new
			// If it is new, it's a copy, and we don't want to steal the assignment from the original content
			if (!$entity->isNew() && isset($originalInlineAssignments[ $mediaItem->id ])) {
				$original = $originalInlineAssignments[ $mediaItem->id ];

				$assignment->set('id', $original->id);
				$assignment->set('createdBy', $original->createdBy);
				$assignment->set('createdOn', $original->createdOn);

				$assignment->setNew(false);
			}

			$mediaAssignments[] = $assignment;

			// Remember the media id
			$mediaIdsFound[] = $mediaItem->id;
		}

		$entity->set('mediaAssignments', $mediaAssignments);
	}


	/**
	 * @param \Dom\HTMLDocument $dom
	 * @param \Dom\Element $tag
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return array
	 */
	protected static function extractMediaAttributes(HTMLDocument $dom, Element $tag, EntityInterface $entity): array {
		// The attributes are stored as a JSON string in the textContent
		$attributes = json_decode($tag->textContent, true);

		if (
			!is_array($attributes) ||
			!isset($attributes['mediaId']) ||
			!isset($entity->mediaAssignments['inlineImgTag'][ $attributes['mediaId'] ])
		) {
			// Replace the node with an empty string
			$tag->parentNode->replaceChild($dom->createTextNode(''), $tag);

			return [[], null];
		}

		/**
		 * @var \Awyiss\Model\Entity\Media $media
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$media = $entity->mediaAssignments['inlineImgTag'][ $attributes['mediaId'] ]->media;

		return [$attributes, $media];
	}


	/**
	 * Checks if the given field is valid for the entity
	 * or if it exists in the attributes of the entity.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $field
	 * @return bool
	 */
	protected static function fieldIsValid(EntityInterface $entity, string $field): bool {
		// Field is valid if it exists in the entity and is a string
		if ($entity->has($field)) {
			return is_string($entity->get($field));
		}

		// If the entity has no attributes or the attributes are not an instance of Entity,
		// the field is not valid
		if (
			!$entity->has('attributes') || !($entity->get('attributes') instanceof Entity)
		) {
			return false;
		}


		/**
		 * Field is valid if it exists in the attributes and is a string
		 *
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		return $entity->attributes->has($field) && is_string($entity->attributes->get($field));
	}


	/**
	 * Returns the default fields to check,
	 * including the attributes with input type `texteditor`
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return array
	 * @noinspection DuplicatedCode
	 */
	protected static function getDefaultFields(EntityInterface $entity): array {
		/** @var \Awyiss\Model\Table $table */
		$table = FactoryLocator::get('Table')->get($entity->getSource());


		if (!$table->hasBehavior('Attributes') || !$table->hasAttributes()) {
			return static::$defaultFields;
		}

		$fields = static::$defaultFields;

		foreach ($table->getAttributes() as $attribute) {
			if ($attribute->inputType !== 'texteditor') {
				continue;
			}

			$field = Inflector::variable($attribute->identifier);
			if (!in_array($field, $fields, true)) {
				$fields[] = $field;
			}
		}

		return $fields;
	}
}
