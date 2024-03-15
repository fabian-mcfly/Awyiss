<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Enum\PublicationDataType;
use Awyiss\Model\Table;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\Type\EnumType;
use Cake\Validation\Validator;


/**
 * PublicationDataTable class
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
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $ao_validator The validator that can be modified to add some rules to it.
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('foreignId');
		$ao_validator->add('foreignId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->notEmptyString('type');
		$ao_validator->add('type', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 20]],
		]);


		$ao_validator->add('dateTime', [
			'dateTime' => ['rule' => 'dateTime'],
		]);


		return $ao_validator;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		$this->getSchema()->setColumnType('type', EnumType::from(PublicationDataType::class));
	}
}
