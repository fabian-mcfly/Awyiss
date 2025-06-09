<?php declare(strict_types=1);


namespace Awyiss\Utility\Content;


use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Media\MediaRenderOptions;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\FactoryLocator;
use Cake\View\View;
use DOMDocument;
use DOMElement;
use DOMXPath;


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
 * This is necessary to ensure that images, inserted in the rich text editor
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
	 * @throws \DOMException
	 */
	public static function replaceImageTags(EntityInterface $entity, array $fields = [], ?EntityInterface $referenceEntity = null): void {
		$la_fields = $fields ?: static::getDefaultFields($entity);

		foreach ($la_fields as $ls_field) {
			if (!$entity->has($ls_field) || !is_string($entity->get($ls_field))) {
				continue;
			}

			static::replaceImageTagsInField($entity, $ls_field, null, $referenceEntity);
		}

		if ($entity->has('_translations')) {
			foreach ($entity->get('_translations') as $lo_translation) {
				static::replaceImageTags($lo_translation, $fields, $entity);
			}
		}
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array $fields
	 * @param \Cake\Datasource\EntityInterface|null $referenceEntity
	 * @return void
	 * @throws \DOMException
	 */
	public static function rebuildSimpleImageTags(EntityInterface $entity, array $fields = [], ?EntityInterface $referenceEntity = null): void {
		$la_fields = $fields ?: static::getDefaultFields($entity);

		foreach ($la_fields as $ls_field) {
			if (!$entity->has($ls_field) || !is_string($entity->get($ls_field))) {
				continue;
			}

			static::rebuildSimpleImageTagsInField($entity, $ls_field, null, $referenceEntity);
		}

		if ($entity->has('_translations')) {
			foreach ($entity->get('_translations') as $lo_translation) {
				static::rebuildSimpleImageTags($lo_translation, $fields, $entity);
			}
		}
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
		$la_fields = $fields ?: static::getDefaultFields($entity);

		foreach ($la_fields as $ls_field) {
			if (!$entity->has($ls_field) || !is_string($entity->get($ls_field))) {
				continue;
			}

			static::replaceCustomImageTagsInField($entity, $view, $mediaRenderOptions, $ls_field, null, $referenceEntity);
		}

		if ($entity->has('_translations')) {
			foreach ($entity->get('_translations') as $lo_translation) {
				static::replaceCustomImageTags($lo_translation, $view, $mediaRenderOptions, $fields, $entity);
			}
		}
	}


	/**
	 * Returns the default fields to check,
	 * including the attributes with input type `texteditor`
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return array
	 * @noinspection DuplicatedCode
	 */
	public static function getDefaultFields(EntityInterface $entity): array {
		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = FactoryLocator::get('Table')->get($entity->getSource());

		if (!$lo_table->hasBehavior('Attributes') || !$lo_table->hasAttributes()) {
			return static::$defaultFields;
		}

		$la_fields = static::$defaultFields;

		foreach ($lo_table->getAttributes() as $lo_attribute) {
			if ($lo_attribute->inputType === 'texteditor') {
				$la_fields[] = $lo_attribute->identifier;
			}
		}

		return $la_fields;
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
		$ls_value = $value ?? $entity->get($field) ?? '';
		$lb_isDirty = $entity->isDirty($field);

		if (!is_string($ls_value) || !str_contains($ls_value, '<awyiss-responsive-image')) {
			return $ls_value;
		}

		/** @var \Awyiss\View\Helper\MediaHelper $lo_mediaHelper */
		$lo_mediaHelper = $view->helpers()->get('Media');

		$lo_dom = static::getDomDocument($ls_value);

		// Create an XPath instance
		$lo_xpath = new DOMXPath($lo_dom);

		// Find all <awyiss-responsive-image> tags
		$lo_tags = $lo_xpath->query('//awyiss-responsive-image');

		foreach ($lo_tags as $lo_tag) {
			[$la_attributes, $lo_media] = self::extractMediaAttributes($lo_dom, $lo_tag, $referenceEntity ?? $entity);
			unset($la_attributes['mediaId']);

			if (!$lo_media) {
				continue;
			}

			$lo_mediaRenderOptions = $mediaRenderOptions->withAttributes($la_attributes);
			if ($la_attributes['width'] ?? null) {
				$lo_mediaRenderOptions = $lo_mediaRenderOptions
					->withWidth((int)$la_attributes['width'])
					->withResponsive(false);
			}
			if ($la_attributes['height'] ?? null) {
				$lo_mediaRenderOptions = $lo_mediaRenderOptions
					->withHeight((int)$la_attributes['height'])
					->withResponsive(false);
			}

			$ls_htmlTag = $lo_mediaHelper->htmlTag($lo_media, $lo_mediaRenderOptions);

			$ls_value = str_replace($lo_tag->ownerDocument->saveHTML($lo_tag), $ls_htmlTag, $ls_value);
		}

		$entity->set($field, trim($ls_value) ?: null);
		$entity->setDirty($field, $lb_isDirty);

		return $entity->get($field);
	}


	/**
	 * Rebuilds the image tags in the given entity
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $field
	 * @param string|null $value
	 * @param \Cake\Datasource\EntityInterface|null $referenceEntity
	 * @return string|null
	 * @throws \DOMException
	 */
	public static function rebuildSimpleImageTagsInField(
		EntityInterface $entity,
		string $field,
		?string $value = null,
		?EntityInterface $referenceEntity = null
	): ?string {
		$ls_value = $value ?? $entity->get($field) ?? '';
		$lb_isDirty = $entity->isDirty($field);

		if (!is_string($ls_value) || !str_contains($ls_value, '<awyiss-responsive-image')) {
			return $ls_value;
		}

		$lo_dom = static::getDomDocument($ls_value);

		// Create an XPath instance
		$lo_xpath = new DOMXPath($lo_dom);

		// Find all <awyiss-responsive-image> tags
		$lo_tags = $lo_xpath->query('//awyiss-responsive-image');

		foreach ($lo_tags as $lo_tag) {
			[$la_attributes, $lo_media] = self::extractMediaAttributes($lo_dom, $lo_tag, $referenceEntity ?? $entity);

			if (!$lo_media) {
				continue;
			}

			// Create a new <img> tag
			$lo_imgTag = $lo_dom->createElement('img');
			$lo_imgTag->setAttribute('src', $lo_media->path);

			// Set the other attributes
			foreach ($la_attributes as $ls_key => $ls_value) {
				if ($ls_key === 'mediaId') {
					continue;
				}

				$lo_imgTag->setAttribute($ls_key, (string)$ls_value);
			}

			// Replace the custom tag with the <img> tag
			$lo_tag->parentNode->replaceChild($lo_imgTag, $lo_tag);
		}

		$entity->set($field, trim(static::getBody($lo_dom)) ?: null);
		$entity->setDirty($field, $lb_isDirty);

		return $entity->get($field);
	}


	/**
	 * @param string|null $value
	 * @param array $media
	 * @param bool $absolutePath
	 * @return string|null
	 * @throws \DOMException
	 */
	public static function rebuildSimpleImageTagsInText(?string $value, array $media, bool $absolutePath = false): ?string {
		if (!is_string($value) || !str_contains($value, '<awyiss-responsive-image')) {
			return $value;
		}


		$lo_dom = static::getDomDocument($value);

		// Create an XPath instance
		$lo_xpath = new DOMXPath($lo_dom);

		// Find all <awyiss-responsive-image> tags
		$lo_tags = $lo_xpath->query('//awyiss-responsive-image');

		$ls_baseUrl = $absolutePath ? Router::url('/', true) : '';

		foreach ($lo_tags as $lo_tag) {
			$la_attributes = json_decode($lo_tag->textContent, true);

			if (
				!is_array($la_attributes) ||
				!isset($la_attributes['mediaId']) ||
				!isset($media[ $la_attributes['mediaId'] ])
			) {
				continue;
			}

			// Create a new <img> tag
			$lo_imgTag = $lo_dom->createElement('img');
			$lo_imgTag->setAttribute('src', $ls_baseUrl . $media[ $la_attributes['mediaId'] ]->path);

			// Set the other attributes
			foreach ($la_attributes as $ls_key => $ls_value) {
				if ($ls_key === 'mediaId') {
					continue;
				}

				$lo_imgTag->setAttribute($ls_key, (string)$ls_value);
			}

			// Replace the custom tag with the <img> tag
			$lo_tag->parentNode->replaceChild($lo_imgTag, $lo_tag);
		}

		return trim(static::getBody($lo_dom)) ?: null;
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param string $field
	 * @param string|null $value
	 * @param \Cake\Datasource\EntityInterface|null $referenceEntity
	 * @return string|null
	 * @throws \DOMException
	 */
	public static function replaceImageTagsInField(
		EntityInterface $entity,
		string $field,
		?string $value = null,
		?EntityInterface $referenceEntity = null
	): ?string {
		$ls_value = $value ?? $entity->get($field) ?? '';

		if (!is_string($ls_value) || !str_contains($ls_value, '<img')) {
			return $ls_value;
		}

		$lo_dom = static::getDomDocument($ls_value);

		// Create an XPath instance
		$lo_xpath = new DOMXPath($lo_dom);

		// Find all <img> tags
		$lo_tags = $lo_xpath->query('//img');

		$la_foundSources = [];

		foreach ($lo_tags as $lo_tag) {
			// Get all attributes
			$la_attributes = [];
			foreach ($lo_tag->attributes as $lo_attribute) {
				$la_attributes[ Inflector::variable($lo_attribute->name) ] = $lo_attribute->value;
			}

			// Get the src attribute
			if ($la_attributes['src'] ?? null) {
				$la_foundSources[] = [
					'src' => $la_attributes['src'],
					'attributes' => $la_attributes,
					'node' => $lo_tag,
				];
			}
		}

		/** @var \Awyiss\Model\Table $lo_table */
		$lo_table = FactoryLocator::get('Table')->get('Media');
		$lo_media = $lo_table->find('all')->where([
			'path IN' => array_map(function ($la_foundSource) {
				return $la_foundSource['src'];
			}, $la_foundSources),
		])->all()->indexBy('path');

		if (!$lo_media->count()) {
			return $ls_value;
		}

		$la_media = $lo_media->toArray();
		foreach ($la_foundSources as $la_source) {
			if (!isset($la_media[ $la_source['src'] ])) {
				continue;
			}

			$lo_mediaEntity = $la_media[ $la_source['src'] ];

			// Create a new custom image tag
			$lo_customTag = $lo_dom->createElement('awyiss-responsive-image');

			$la_attributes = $la_source['attributes'];
			// Remove the source
			unset($la_attributes['src']);
			// Add the media id
			$la_attributes['mediaId'] = (string)$lo_mediaEntity->id;

			// Set the JSON string as the content of the custom tag
			$lo_customTag->textContent = json_encode($la_attributes);

			// Replace the original image tag with the custom tag
			$la_source['node']->parentNode->replaceChild($lo_customTag, $la_source['node']);
		}

		// Build media assignments
		static::buildMediaAssignments($referenceEntity ?? $entity, $la_media);

		$entity->set($field, trim(static::getBody($lo_dom)) ?: null);

		return $entity->get($field);
	}


	/**
	 * Creates a DOMDocument from the given HTML string
	 *
	 * @param string $value
	 * @return \DOMDocument
	 */
	protected static function getDomDocument(string $value): DOMDocument {
		$lo_dom = new DOMDocument('1.0', 'UTF-8');

		// Suppress errors due to malformed HTML
		libxml_use_internal_errors(true);

		// Load the HTML string into the DOMDocument
		$lo_dom->loadHTML('<!DOCTYPE html>' . mb_encode_numericentity($value, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'));

		// Clear any errors collected during loadHTML
		libxml_clear_errors();

		return $lo_dom;
	}


	/**
	 * Returns the contents of `<body>`-tag of the given DOMDocument as a string
	 *
	 * @param \DOMDocument $dom
	 * @return string|false
	 * @noinspection DuplicatedCode
	 */
	protected static function getBody(DOMDocument $dom): string|false {
		if ($dom->doctype) {
			// Remove the doctype
			$dom->removeChild($dom->doctype);
		}

		// Remove the opening and closing `<html>`-tags
		$dom->replaceChild($dom->firstChild->firstChild, $dom->firstChild);

		// Remove the opening and closing `<body>`-tags
		$lo_body = $dom->getElementsByTagName('body')->item(0);

		if (!$lo_body) {
			// Return the cleaned HTML
			return $dom->saveHTML();
		}

		while ($lo_body->firstChild) {
			$dom->appendChild($lo_body->firstChild);
		}

		$dom->removeChild($lo_body);

		// Return the cleaned HTML
		return $dom->saveHTML();
	}


	/**
	 * Builds the media assignments for the given entity
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param array<string, \Awyiss\Model\Entity\Media> $media
	 * @return void
	 */
	protected static function buildMediaAssignments(EntityInterface $entity, array $media): void {
		$la_mediaAssignments = $entity->get('mediaAssignments') ?: [];
		$lo_mediaAssignmentsTable = FactoryLocator::get('Table')->get('MediaAssignments');

		$la_originalMediaAssignments = $entity->hasOriginal('mediaAssignments') ? $entity->getOriginal('mediaAssignments') : [];
		$la_originalInlineAssignments = $la_originalMediaAssignments['inlineImgTag'] ?? [];

		// Remember the media ids
		$la_mediaIdsFound = [];
		foreach ($media as $lo_media) {
			// If the media id is already in the assignments, skip it
			// There's no need to create multiple assignments for the same media
			if (in_array($lo_media->id, $la_mediaIdsFound)) {
				continue;
			}

			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_assignment = $lo_mediaAssignmentsTable->newDefaultEntity();

			$lo_assignment->patch([
				'mediaElementId' => 5, // 5 for `inline_img_tag`
				'mediaElementSelectorIdentifier' => 'inline_img_tag',
				'mediaId' => $lo_media->id,
				'scope' => Inflector::underscore($entity->getSource()),
			]);

			// Copy the original assignment ID if it exists, but only if the entity is not new
			// If it is new, it's a copy, and we don't want to steal the assignment from the original content
			if (!$entity->isNew() && isset($la_originalInlineAssignments[ $lo_media->id ])) {
				$lo_original = $la_originalInlineAssignments[ $lo_media->id ];

				$lo_assignment->set('id', $lo_original->id);
				$lo_assignment->set('createdBy', $lo_original->createdBy);
				$lo_assignment->set('createdOn', $lo_original->createdOn);

				$lo_assignment->setNew(false);
			}

			$la_mediaAssignments[] = $lo_assignment;

			// Remember the media id
			$la_mediaIdsFound[] = $lo_media->id;
		}

		$entity->set('mediaAssignments', $la_mediaAssignments);
	}


	/**
	 * @param \DOMDocument $dom
	 * @param \DOMElement $tag
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @return array
	 */
	protected static function extractMediaAttributes(DOMDocument $dom, DOMElement $tag, EntityInterface $entity): array {
		// The attributes are stored as a JSON string in the textContent
		$la_attributes = json_decode($tag->textContent, true);

		if (
			!is_array($la_attributes) ||
			!isset($la_attributes['mediaId']) ||
			!isset($entity->mediaAssignments['inlineImgTag'][ $la_attributes['mediaId'] ])
		) {
			// Replace the node with an empty string
			$tag->parentNode->replaceChild($dom->createTextNode(''), $tag);
		}

		// Get the media ID
		$li_mediaId = $la_attributes['mediaId'];
		/**
		 * @var \Awyiss\Model\Entity\Media $lo_media
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$lo_media = $entity->mediaAssignments['inlineImgTag'][ $li_mediaId ]->media;

		return [$la_attributes, $lo_media];
	}
}
