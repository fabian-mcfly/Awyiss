<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\Media;
use Awyiss\Model\Enum\ProcessStatus;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Core\Configure;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;
use finfo;


/**
 * Media Model
 *
 * @property \Awyiss\Model\Table\MediaFoldersTable&\Awyiss\ORM\Association\BelongsTo $MediaFolders
 * @property \Awyiss\Model\Table\MediaFoldersTable&\Awyiss\ORM\Association\HasMany $MediaResizedImages
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

		$this->hasMany('MediaResizedImages', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'saveStrategy' => 'replace',
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
				if (!$ao_entity->file || $ao_entity->file?->getError()) {
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
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		$this->getSchema()->setColumnType('preview', EnumType::from(ProcessStatus::class));
		$this->getSchema()->setColumnType('webp', EnumType::from(ProcessStatus::class));
		$this->getSchema()->setColumnType('crop', 'json');
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
