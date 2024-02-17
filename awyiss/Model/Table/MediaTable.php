<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use ArrayObject;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Core\Configure;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;
use finfo;


/**
 * Media Model
 *
 * @property \Awyiss\Model\Table\MediaFoldersTable&\Awyiss\ORM\Association\BelongsTo $MediaFolders
 * @method \Awyiss\Model\Entity\Media newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class MediaTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = true;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'media';


	/**
	 * @var \finfo Fileinfo object for the given file object (available only on file upload)
	 */
	protected static finfo $finfo;
	/**
	 * @var array<int, array<int, \Awyiss\Model\Entity\Media>>
	 */
	protected static array $media = [];
	/**
	 * @var array<int, \Awyiss\Model\Entity\MediaFolder>
	 */
	protected static array $mediaFolders;
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => ['alt'],
	];


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'allowAggregation' => false,
		'associationName' => 'MediaFolders',
		'enabled' => true,
		'finder' => 'forCurrentLanguage',
		'identifier' => 'mediaFolder',
	];
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['mediaFolderId'],
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('MediaFolders', [
			'joinType' => 'INNER',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			'mediaFolderId',
			'name',
			'file',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('mediaFolderId');
		$ao_validator->add('mediaFolderId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('name');
		$ao_validator->add('name', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('alt', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$ao_validator->add('metaData', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function ($ax_value) {
					return strlen(json_encode($ax_value)) <= 16777215;
				},
			],
		]);


		$ao_validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$ao_validator->notEmptyFile('file', null, 'create');
		$ao_validator->add('file', [
			'uploadedFile' => ['rule' => 'uploadedFile'],
		]);


		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 * @return RulesChecker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add(
			function (Media $ao_entity): bool|string {
				if ($ao_entity->file->getError()) {
					return true;
				}

				$ls_extension = $ao_entity->extension;

				if (!$ls_extension) {
					return __df($this->getI18nDomain(), 'validation', 'error_media_has_file_extension');
				}
				$lo_stream = $ao_entity->file->getStream();
				$ls_tempName = $lo_stream->getMetadata('uri');

				$ao_entity->mimeType = static::getFinfo()->file($ls_tempName, FILEINFO_MIME_TYPE);

				$ls_knownExtensions = static::getFinfo()->file($ls_tempName, FILEINFO_EXTENSION);
				$la_knownExtensions = explode('/', $ls_knownExtensions);

				if ($ls_knownExtensions === '???' || !in_array($ls_extension, $la_knownExtensions)) {
					//Fallback if extension isn't known for the mimetype
					$la_knownExtensions = Configure::read('MimeTypes.' . str_replace('.', '-', $ao_entity->mimeType));
				}

				if (empty($la_knownExtensions) || !in_array($ls_extension, $la_knownExtensions)) {
					return __df(
						$this->getI18nDomain(),
						'validation',
						'error_media_mime_type_matches_extension',
						$ls_extension,
						$ao_entity->mimeType
					);
				}

				return true;
			},
			'validFileNameExtension',
			[
				'errorField' => 'file',
			]
		);


		return $ao_rules;
	}


	/**
	 * Before saving a file, make sure its name is unique, path is set
	 * image dimensions are known and file extension matches the mimetype
	 *
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Media $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $ao_event, Media $ao_entity, ArrayObject $ao_options): void {
		$lb_isNew = $ao_entity->isNew();

		if (!isset(static::$mediaFolders)) {
			/**
			 * @var \Cake\Collection\Iterator\TreeIterator $lo_mediaFolderse
			 * @noinspection PhpPossiblePolymorphicInvocationInspection
			 */
			$lo_mediaFolderse = $this->getBehavior('Categories')->getCategories(true)->compile(false);
			static::$mediaFolders = $lo_mediaFolderse->indexBy('id')->toArray();
		}

		if (!isset(static::$media[ $ao_entity->mediaFolderId ])) {
			/** @var \Cake\Collection\Iterator\TreeIterator $lo_mediaFolderse */
			$lo_media = $this->find()->where(['media_folder_id' => $ao_entity->mediaFolderId])->all();
			static::$media[ $ao_entity->mediaFolderId ] = $lo_media->indexBy('name')->toArray();
		}

		if (!$ao_entity->extension) {
			$la_knownExtensions = Configure::read('MimeTypes.' . str_replace('.', '-', $ao_entity->mimeType));

			if (!$la_knownExtensions) {
				$ao_event->stopPropagation();

				$ao_entity->setError(
					'name',
					__df(strtolower($this->getI18nDomain()), 'validation', 'error_media_has_file_extension'),
					true
				);

				return;
			}

			$ao_entity->name .= '.' . current($la_knownExtensions);
		}

		if ($ao_entity->file && !$ao_entity->file->getError()) {
			$lo_stream = $ao_entity->file->getStream();
			$ls_tempName = $lo_stream->getMetadata('uri');

			if ($ao_entity->isImage()) {
				$la_imageSize = getimagesize($ls_tempName);

				$ao_entity->width = $la_imageSize[0];
				$ao_entity->height = $la_imageSize[1];
				$ao_entity->preview = ProcessStatus::NotRequired;

				/*if ($ao_entity->mimeType !== 'image/webp') {
					$la_exifData = exif_read_data($ls_tempName, '', true);
				}*/
			}
			else {
				$ao_entity->width = null;
				$ao_entity->height = null;
				$ao_entity->preview = ProcessStatus::Undefined;

				if ($ao_entity->mimeType === 'image/svg+xml') {
					$la_dimensions = $this->getSvgDimensions(file_get_contents($ls_tempName));

					$ao_entity->width = $la_dimensions['width'];
					$ao_entity->height = $la_dimensions['height'];
				}
			}

			$ao_entity->webp = $ao_entity->mimeType === 'image/webp' ? ProcessStatus::NotRequired : ProcessStatus::Undefined;

			if ($lb_isNew && LocalConfig::read('upload.autoOverwrite', false) === true) {
				$lo_currentMedia = static::$media[ $ao_entity->mediaFolderId ][ $ao_entity->name ] ?? null;
				if ($lo_currentMedia) {
					$ao_entity->setNew(false);
					$ao_entity->set([
						'id' => $lo_currentMedia->id,
						'alt' => $ao_entity->alt ?? $lo_currentMedia->alt,
						'systemOrder' => $lo_currentMedia->systemOrder,
						'createdBy' => $lo_currentMedia->createdBy,
						'createdOn' => $lo_currentMedia->createdOn,
					], [
						'guard' => false,
					]);

					$ao_entity->setDirty('systemOrder', false);

					if ($lo_currentMedia->attributes) {
						$ao_entity->attributes = $lo_currentMedia->attributes;
					}
				}
			}
			else {
				$this->ensureUniqueFileName($ao_entity);
			}
		}
		elseif ($ao_entity->isDirty('name') || $ao_entity->isDirty('mediaFolderId')) {
			$this->ensureUniqueFileName($ao_entity);
		}

		$ls_path = 'media/';

		if (isset(static::$mediaFolders[ $ao_entity->mediaFolderId ])) {
			$ls_path = static::$mediaFolders[ $ao_entity->mediaFolderId ]->path . '/';
		}

		$ao_entity->path = $ls_path . $ao_entity->name;
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Media $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(EventInterface $ao_event, Media $ao_entity, ArrayObject $ao_options): void {
		if ($ao_entity->file && !$ao_entity->file->getError()) {
			$ao_entity->deleteConvertedFiles();

			$ao_entity->file->moveTo(WWW_ROOT . str_replace('/', DS, $ao_entity->path));

			if ($ao_entity->hasOriginal('path') && $ao_entity->getOriginal('path') !== $ao_entity->get('path')) {
				unlink(WWW_ROOT . str_replace('/', DS, $ao_entity->getOriginal('path')));
			}
		}
		elseif ($ao_entity->hasOriginal('path') && $ao_entity->getOriginal('path') !== $ao_entity->get('path')) {
			$ao_entity->moveConvertedFiles();

			rename(
				WWW_ROOT . str_replace('/', DS, $ao_entity->getOriginal('path')),
				WWW_ROOT . str_replace('/', DS, $ao_entity->get('path'))
			);
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\Media $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterDelete(EventInterface $ao_event, Media $ao_entity, ArrayObject $ao_options): void {
		$ls_sourceFile = $ao_entity->path;
		if ($ls_sourceFile && is_file($ls_sourceFile)) {
			unlink($ls_sourceFile);
		}

		$ao_entity->deleteConvertedFiles();
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		$this->getSchema()->setColumnType('preview', EnumType::from(ProcessStatus::class));
		$this->getSchema()->setColumnType('webp', EnumType::from(ProcessStatus::class));
	}


	/**
	 * @param \Awyiss\Model\Entity\Media $ao_entity
	 * @return void
	 */
	protected function ensureUniqueFileName(Media $ao_entity): void {
		$ls_extension = $ao_entity->extension;
		$ls_fileName = $ao_entity->cleanName;

		$la_conditions = [
			'name' => $ao_entity->name,
			'media_folder_id' => $ao_entity->mediaFolderId,
		];

		$ls_primaryKey = $this->getPrimaryKey();
		$li_id = $ao_entity->get($ls_primaryKey);
		if ($li_id) {
			$la_conditions['NOT'] = [$this->getAlias() . '.' . $ls_primaryKey => $li_id];
		}


		$ls_field = $this->getSchema()->getColumn('slug');
		$li_length = $ls_field ? $ls_field['length'] : 0;

		$li_i = 1;
		$ls_suffix = '';

		//As long as a page with the same slug exists, append an increasing number to the slug and try again
		while ($this->exists($la_conditions)) {
			$li_i++;
			$ls_suffix = '-' . $li_i;

			if ($li_length && (mb_strlen($ls_fileName . $ls_suffix) > $li_length)) {
				$ls_fileName = mb_substr($ls_fileName, 0, $li_length - mb_strlen($ls_suffix));
			}

			$la_conditions['name'] = $ls_fileName . $ls_suffix . '.' . $ls_extension;
		}

		//Append the suffix, if it's not empty
		if ($ls_suffix) {
			$ao_entity->name = $ls_fileName . $ls_suffix . '.' . $ls_extension;
		}
	}


	/**
	 * @param string $as_fileContents
	 * @return array
	 */
	protected function getSvgDimensions(string $as_fileContents): array {
		$lf_width = $lf_height = null;

		/** @noinspection RegExpRedundantEscape */
		preg_match('/viewbox="(?<sizes>[0-9\. ]*)"/i', $as_fileContents, $la_matches);
		if (!empty($la_matches['sizes'])) {
			$la_coordinates = explode(' ', $la_matches['sizes'], 4);

			$lf_width = (float)$la_coordinates[2];
			$lf_height = (float)$la_coordinates[3];
		}
		else {
			preg_match('/<svg[^>]*\s(width|height)="(\d+)"\s.*?(width|height)="(\d+)"/', $as_fileContents, $la_matches);
			if ($la_matches) {
				if (strtolower($la_matches[1]) === 'width') {
					$lf_width = (float)$la_matches[2];
					$lf_height = (float)$la_matches[4];
				}
				else {
					$lf_width = (float)$la_matches[4];
					$lf_height = (float)$la_matches[2];
				}
			}
		}

		return [
			'width' => $lf_width,
			'height' => $lf_height,
		];
	}


	/**
	 * Initialize this only once, to reduce overhead
	 *
	 * @return \finfo
	 */
	protected static function getFinfo(): finfo {
		if (!isset(static::$finfo)) {
			static::$finfo = new finfo();
		}


		return static::$finfo;
	}
}
