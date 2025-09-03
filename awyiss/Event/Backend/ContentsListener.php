<?php declare(strict_types=1);


namespace Awyiss\Event\Backend;


use ArrayObject;
use Awyiss\Event\EventListenerTrait;
use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table\ContentsTable;
use Cake\Datasource\FactoryLocator;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Dom\HTMLDocument;


/**
 * Event listeners for the Contents scope of the backend
 */
class ContentsListener implements EventListenerInterface {
	use EventListenerTrait;


	/**
	 * @var string
	 */
	protected static string $scope;
	/**
	 * The default fields to check for anchor links
	 *
	 * @var array<int, string>
	 */
	protected static array $defaultFields = ['text'];
	/**
	 * @var array|null
	 */
	protected static ?array $texteditorFields = null;
	/**
	 * @var array|null
	 */
	protected static ?array $pages = null;


	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return [
			'Model.Contents.beforeSave' => 'beforeSave',
		];
	}


	/**
	 * @param \Cake\Event\Event $event
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param \ArrayObject $options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(Event $event, Content $entity, ArrayObject $options): void {
		// Unset titleTag and subtitleTag if title and subtitle are empty
		if (!$entity->title && $entity->titleTag) {
			$entity->titleTag = null;
		}

		if (!$entity->subtitle && $entity->subtitleTag) {
			$entity->subtitleTag = null;
		}

		$this->fixAnchorLinks($entity, $event->getSubject());
	}


	/**
	 * @param \Awyiss\Model\Entity\Content $entity
	 * @param \Awyiss\Model\Table\ContentsTable $table
	 * @return void
	 */
	protected function fixAnchorLinks(Content $entity, ContentsTable $table): void {
		$la_fields = $this->getTextFields($table);
		$la_pages = $this->getPages();

		foreach ($la_fields as $ls_field) {
			if (
				!$entity->get($ls_field) ||
				!str_contains($entity->get($ls_field), '#') ||
				!$entity->get('pageId') ||
				!isset($la_pages[ $entity->get('pageId') ])
			) {
				continue;
			}

			$lo_dom = static::getDom($entity->get($ls_field));

			$lo_elements = $lo_dom->querySelectorAll('a[href^="#"]');

			if ($lo_elements->length === 0) {
				continue;
			}

			$ls_slug = $la_pages[ $entity->get('pageId') ] . '/';

			/** @var \Dom\Element $element */
			foreach ($lo_elements as $lo_element) {
				$lo_element->setAttribute('href', $ls_slug . $lo_element->getAttribute('href'));
			}

			// Set the cleaned value back to the entity
			$entity->set($ls_field, trim(static::getBody($lo_dom)) ?: null);
		}
	}


	/**
	 * Returns the text fields to check for anchor links
	 *
	 * @param \Awyiss\Model\Table\ContentsTable $table
	 * @return array
	 */
	public function getTextFields(ContentsTable $table): array {
		if (isset(static::$texteditorFields)) {
			return static::$texteditorFields;
		}

		static::$texteditorFields = static::$defaultFields;

		if (!$table->hasBehavior('Attributes') || !$table->hasAttributes()) {
			return static::$texteditorFields;
		}

		foreach ($table->getAttributes() as $lo_attribute) {
			if ($lo_attribute->inputType === 'texteditor') {
				static::$texteditorFields[] = $lo_attribute->identifier;
			}
		}

		return static::$texteditorFields;
	}


	/**
	 * @return array
	 */
	protected function getPages(): array {
		if (isset(static::$pages)) {
			return static::$pages;
		}

		$lo_pages = FactoryLocator::get('Table')->get('Pages')->find('all', skipPageRoleCheck: true)->all();

		static::$pages = $lo_pages->combine('id', function (Page $page): string {
			return $page->languageShortcode . '/' . $page->slug;
		})->toArray();

		return static::$pages;
	}


	/**
	 * Creates a \Dom\HTMLDocument from the given HTML string
	 *
	 * @param string $value
	 * @return \Dom\HTMLDocument
	 */
	protected static function getDom(string $value): HTMLDocument {
		return HTMLDocument::createFromString($value, LIBXML_NOERROR, 'UTF-8');
	}


	/**
	 * Returns the contents of `<body>`-tag of the given \Dom\HTMLDocument as a string
	 *
	 * @param \Dom\HTMLDocument $dom
	 * @return string|false
	 * @noinspection DuplicatedCode
	 */
	protected static function getBody(HTMLDocument $dom): string|false {
		$ls_html = '';

		// Remove the opening and closing `<body>`-tags
		$lo_body = $dom->querySelector('body');

		while ($lo_body->firstChild) {
			$ls_html .= $dom->saveHTML($lo_body->firstChild);
			$lo_body->removeChild($lo_body->firstChild);
		}

		// Return the cleaned HTML
		return $ls_html;
	}
}
