<?php declare(strict_types=1);


namespace Awyiss\Model\Enum\Survey;


use Awyiss\Utility\Inflector;
use Cake\Database\Type\EnumLabelInterface;


/**
 * Survey QuestionType Enum
 */
enum QuestionType: string implements EnumLabelInterface {
	case SingleChoice = 'singleChoice';
	case MultiChoice = 'multipleChoice';
	case FreeText = 'freeText';
	case InfoText = 'infoText';


	/**
	 * @return string
	 */
	public function label(): string {
		return __d('SurveyQuestions', 'question_type_' . Inflector::underscore($this->value));
	}
}
