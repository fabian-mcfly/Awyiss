<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Media;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Core\Configure;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;
use finfo;
use Laminas\Diactoros\UploadedFile;


/**
 * Media Model
 *
 * @property \Awyiss\Model\Table\MediaAssignmentsTable&\Awyiss\ORM\Association\HasMany $MediaAssignments
 * @property \Awyiss\Model\Table\MediaFoldersTable&\Awyiss\ORM\Association\BelongsTo $MediaFolders
 * @property \Awyiss\Model\Table\MediaFoldersTable&\Awyiss\ORM\Association\HasMany $MediaResizedImages
 * @method \Awyiss\Model\Entity\Media newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class MediaTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'media';


	/**
	 * @var \finfo Fileinfo object for the given file object (available only on file upload)
	 */
	protected static finfo $finfo;


	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => ['alt'],
		'realm' => Awyiss::REALM_FRONTEND,
	];


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'allowAggregation' => false,
		'associationName' => 'MediaFolders',
		'enabled' => true,
		/** @uses \Awyiss\Model\Table::findForCurrentLanguage() */
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
		$this->hasMany('MediaAssignments', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'saveStrategy' => 'replace',
		]);

		$this->belongsTo('MediaFolders');

		$this->hasMany('MediaResizedImages', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'saveStrategy' => 'replace',
		]);

		$this->hasMany('UrlHistory', [
			'cascadeCallbacks' => true,
			'conditions' => [
				'scope' => 'media',
			],
			'dependent' => true,
			'foreignKey' => 'foreign_key',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'mediaFolderId',
			'name',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('mediaFolderId');
		$validator->add('mediaFolderId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('name');
		$validator->add('name', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('mimeType');
		$validator->add('mimeType', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('path');
		$validator->add('path', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 1124]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('alt', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
		]);


		$validator->add('width', [
			'isFloat' => ['rule' => 'isFloat'],
		]);


		$validator->add('height', [
			'isFloat' => ['rule' => 'isFloat'],
		]);


		$validator->add('averageColor', [
			'exactLength' => [
				'message' => __df($this->getI18nDomain(), 'validation', 'error_exact_length', '6/8'),
				'rule' => function (mixed $averageColor): bool {
					if (!str_starts_with((string)$averageColor, '#')) {
						return strlen((string)$averageColor) == 6 || strlen((string)$averageColor) == 8;
					}

					return strlen((string)$averageColor) == 7 || strlen((string)$averageColor) == 9;
				},
			],
		]);


		/** @var class-string<\Awyiss\Model\Enum\ProcessStatus> $ls_processStatusEnumClass */
		$ls_processStatusEnumClass = App::className('ProcessStatus', 'Model/Enum');
		$validator->add('preview', [
			'enum' => ['rule' => ['enum', $ls_processStatusEnumClass]],
		]);


		$validator->add('avif', [
			'enum' => ['rule' => ['enum', $ls_processStatusEnumClass]],
		]);


		$validator->add('webp', [
			'enum' => ['rule' => ['enum', $ls_processStatusEnumClass]],
		]);



		$validator->add('metaData', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function ($value) {
					return strlen(json_encode($value)) <= 16777215;
				},
			],
		]);


		$validator->allowEmptyArray('crop');
		$validator->add('crop', [
			'isArray' => ['rule' => 'isArray'],
			'maxLengthBytes' => [
				'rule' => function (array|string $value): bool {
					return strlen(json_encode($value)) <= 65535;
				},
			],
		]);


		$validator->notEmptyString('focusPoint');
		$validator->add('focusPoint', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'inList' => [
				'rule' => [
					'inList',
					[
						'0,0',
						'0,1',
						'0,2',
						'1,0',
						'1,1',
						'1,2',
						'2,0',
						'2,1',
						'2,2',
					],
				],
			],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyFile('file', null, 'create');
		$validator->add('file', [
			'uploadedFile' => ['rule' => 'uploadedFile'],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			function (Media $entity): bool|string {
				if (!$entity->file || $entity->file?->getError()) {
					return true;
				}

				$ls_extension = $entity->extension;

				if (!$ls_extension) {
					return __df($this->getI18nDomain(), 'validation', 'error_media_has_file_extension');
				}

				$lo_stream = $entity->file->getStream();
				$ls_tempName = $lo_stream->getMetadata('uri');

				$ls_knownExtensions = static::getFinfo()->file($ls_tempName, FILEINFO_EXTENSION);
				$la_knownExtensions = explode('/', $ls_knownExtensions);

				if ($ls_knownExtensions === '???' || !in_array($ls_extension, $la_knownExtensions)) {
					//Fallback if extension isn't known for the mimetype
					$la_knownExtensions = Configure::read('MimeTypes.' . str_replace('.', '-', $entity->mimeType));
				}

				if (empty($la_knownExtensions) || !in_array($ls_extension, $la_knownExtensions)) {
					return __df(
						$this->getI18nDomain(),
						'validation',
						'error_media_mime_type_matches_extension',
						$ls_extension,
						$entity->mimeType
					);
				}

				return true;
			},
			'validFileNameExtension',
			[
				'errorField' => 'file',
			]
		);


		$rules->add(
			function (Media $entity): bool {
				if ($entity->preview === null) {
					return true;
				}

				/** @var class-string<\Awyiss\Model\Enum\ProcessStatus> $ls_processStatusEnumClass */
				$ls_processStatusEnumClass = App::className('ProcessStatus', 'Model/Enum');

				if (is_int($entity->preview)) {
					return $ls_processStatusEnumClass::tryFrom($entity->preview) !== null;
				}

				return in_array($entity->preview, $ls_processStatusEnumClass::cases());
			},
			'validPreview',
			[
				'errorField' => 'preview',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_preview'),
			]
		);


		$rules->add(
			function (Media $entity): bool {
				if ($entity->webp === null) {
					return true;
				}

				/** @var class-string<\Awyiss\Model\Enum\ProcessStatus> $ls_processStatusEnumClass */
				$ls_processStatusEnumClass = App::className('ProcessStatus', 'Model/Enum');

				if (is_int($entity->webp)) {
					return $ls_processStatusEnumClass::tryFrom($entity->webp) !== null;
				}

				return in_array($entity->webp, $ls_processStatusEnumClass::cases());
			},
			'validWebp',
			[
				'errorField' => 'webp',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_webp'),
			]
		);


		$rules->add(
			function (Media $entity): bool {
				if ($entity->avif === null) {
					return true;
				}

				/** @var class-string<\Awyiss\Model\Enum\ProcessStatus> $ls_processStatusEnumClass */
				$ls_processStatusEnumClass = App::className('ProcessStatus', 'Model/Enum');

				if (is_int($entity->avif)) {
					return $ls_processStatusEnumClass::tryFrom($entity->avif) !== null;
				}

				return in_array($entity->avif, $ls_processStatusEnumClass::cases());
			},
			'validAvif',
			[
				'errorField' => 'avif',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_avif'),
			]
		);


		$rules->addUpdate(
			function (Media $entity) {
				return !$entity->isDirty('mimeType') || $entity->getOriginal('mimeType') === $entity->mimeType;
			},
			'mimetypeNotModified',
			[
				'errorField' => 'file',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_mimetype_not_modified'),
			]
		);


		return $rules;
	}


	/**
	 * Returns the maximum file size in bytes that can be uploaded
	 *
	 * @return int
	 */
	public function getMaxFileSize(): int {
		$ls_maxFileSize = ini_get('upload_max_filesize');
		$ls_maxFileSize = trim($ls_maxFileSize);
		$ls_last = strtolower($ls_maxFileSize[strlen($ls_maxFileSize) - 1]);

		$li_maxFileSize = (int)substr($ls_maxFileSize, 0, -1);

		switch ($ls_last) {
			case 'g':
				$li_maxFileSize *= 1024;
				// no break
			case 'm':
				$li_maxFileSize *= 1024;
				// no break
			case 'k':
				$li_maxFileSize *= 1024;
		}

		return $li_maxFileSize;
	}


	/**
	 * @param \Laminas\Diactoros\UploadedFile $uploadedFile
	 * @param string $extension
	 * @return string
	 */
	public function detectMimeType(UploadedFile $uploadedFile, string $extension): string {
		$lo_stream = $uploadedFile->getStream();
		$ls_tempName = $lo_stream->getMetadata('uri');

		$ls_mimeType = static::getFinfo()->file($ls_tempName, FILEINFO_MIME_TYPE);

		// If the uploaded file's mime type is the same as the detected mime type, return it
		if ($ls_mimeType === $uploadedFile->getClientMediaType()) {
			return $ls_mimeType;
		}

		//Fallback if extension isn't known for the mimetype
		$la_knownExtensionsForDetectedMimeType = Configure::read('MimeTypes.' . str_replace('.', '-', $ls_mimeType), []);
		$la_knownExtensionsForProvidedMimeType = Configure::read('MimeTypes.' . str_replace('.', '-', $uploadedFile->getClientMediaType()), []);

		// If both mime types contain the same, provided extension, return the provided mime type
		if (
			in_array($extension, $la_knownExtensionsForDetectedMimeType) &&
			in_array($extension, $la_knownExtensionsForProvidedMimeType)
		) {
			return $uploadedFile->getClientMediaType();
		}

		return $ls_mimeType;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('meta_data', 'json');

		/** @var class-string<\Awyiss\Model\Enum\ProcessStatus> $ls_processStatusEnumClass */
		$ls_processStatusEnumClass = App::className('ProcessStatus', 'Model/Enum');

		$schema->setColumnType('preview', EnumType::from($ls_processStatusEnumClass));
		$schema->setColumnType('avif', EnumType::from($ls_processStatusEnumClass));
		$schema->setColumnType('webp', EnumType::from($ls_processStatusEnumClass));

		$schema->setColumnType('crop', 'json');
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
