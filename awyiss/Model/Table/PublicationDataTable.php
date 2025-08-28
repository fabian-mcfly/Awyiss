<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Entity\PublicationData;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * PublicationDataTable class
 *
 * @method \Awyiss\Model\Entity\PublicationData newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class PublicationDataTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'publication_data';


	/**
	 * @inheritDoc
	 */
	protected array $audit = [
		'enabled' => false,
	];


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'scope',
			'type',
		], 'create');


		$validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('foreignKey');
		$validator->add('foreignKey', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('type');
		/** @var class-string<\Awyiss\Model\Enum\PublicationDataType> $ls_publicationDataTypeEnumClass */
		$ls_publicationDataTypeEnumClass = App::className('PublicationDataType', 'Model/Enum');
		$validator->add('type', [
			'enum' => ['rule' => ['enum', $ls_publicationDataTypeEnumClass]],
		]);


		$validator->allowEmptyDateTime('dateTime');
		$validator->add('dateTime', [
			'dateTime' => ['rule' => 'dateTime'],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			function (PublicationData $entity): bool {
				/** @var class-string<\Awyiss\Model\Enum\PublicationDataType> $ls_publicationDataTypeEnumClass */
				$ls_publicationDataTypeEnumClass = App::className('PublicationDataType', 'Model/Enum');

				return in_array($entity->type, $ls_publicationDataTypeEnumClass::cases());
			},
			'validType',
			[
				'errorField' => 'type',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_type'),
			]
		);


		$rules->addDelete(
			$rules->isNotLinkedTo('SurveySurveyQuestions', 'surveys'),
			'noLinkedSurveys',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_linked_surveys'),
			]
		);

		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		/** @var class-string<\Awyiss\Model\Enum\PublicationDataType> $ls_publicationDataTypeEnumClass */
		$ls_publicationDataTypeEnumClass = App::className('PublicationDataType', 'Model/Enum');

		$schema->setColumnType('type', EnumType::from($ls_publicationDataTypeEnumClass));
	}
}
