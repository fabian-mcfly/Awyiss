<?php declare(strict_types=1);


namespace Awyiss\Model\Enum\Survey;


use Cake\Database\Type\EnumLabelInterface;


/**
 * Survey QuestionType Enum
 */
enum QuestionType: string implements EnumLabelInterface {
	case SingleChoice = 'single_choice';
	case MultiChoice = 'multiple_choice';
	case FreeText = 'free_text';
	case InfoText = 'info_text';


	/**
	 * @return string
	 */
	public function label(): string {
		return __d('survey_questions', 'question_type_' . $this->value);
	}
}
