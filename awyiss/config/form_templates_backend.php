<?php declare(strict_types=1);

/**
 * @noinspection HtmlUnknownAttribute
 * @noinspection HtmlWrongAttributeValue
 */
return [
	// Wrapper container for checkboxes.
	'checkboxWrapper' => '<div class="FormInput FormInputType-Checkbox FormInputName-{{identifier}} {{containerClass}}">{{label}}{{additionalContent}}</div>',
	// Error message wrapper elements.
	'error' => '<div class="Error">{{content}}</div>',
	// Container for error items.
	'errorList' => '<ul class="ErrorMessages">{{content}}</ul>',
	// Error item wrapper.
	'errorItem' => '<li class="ErrorMessage">{{text}}</li>',
	// Fieldset element used by allControls().
	'fieldset' => '<fieldset{{attrs}}>{{content}}</fieldset>',
	// Wrapper content used to hide other content.
	'hiddenBlock' => '<div class="Hidden">{{content}}</div>',
	// Generic input element.
	'input' => '<input type="{{type}}" name="{{name}}" {{attrs}}>',
	// Container element used by control().
	'inputContainer' => '<div class="FormInput FormInputType-{{type}} FormInputName-{{identifier}}{{required}}{{columnSpan}} {{containerClass}}">{{content}}{{additionalContent}}</div>',
	// Container element used by control() when a field has an error.
	'inputContainerError' => '<div class="FormInput FormInputType-{{type}} FormInputName-{{identifier}}{{required}}{{columnSpan}} {{containerClass}} Error">{{content}}{{error}}{{additionalContent}}</div>',
	// Label element when inputs are not nested inside the label.
	'label' => '<label class="Label{{labelClass}}"{{attrs}}>{{text}}</label>',
	// Legends created by allControls()
	'legend' => '<legend class="Legend">{{text}}</legend>',
	// Multi-Checkbox input set title element.
	'multicheckboxTitle' => '<legend class="Legend">{{text}}</legend>',
	// Label element used for radio and multi-checkbox inputs.
	'nestingLabel' => '{{hidden}}{{input}}<label class="Label"{{attrs}}>{{text}}</label>',
	// Wrapping container for radio input/label,
	'radioWrapper' => '{{label}}',
	// Textarea input element,
	'textarea' => '<textarea name="{{name}}" {{attrs}}>{{value}}</textarea>',
	'translatableText' => '<div class="TranslatableTexts" data-button-title="{{buttonTitle}}"  data-dialog-title="{{dialogTitle}}" data-dialog-apply="{{dialogApply}}" data-dialog-cancel="{{dialogCancel}}">{{controls}}</div>',
	// Container for submit buttons.
	'submitContainer' => '<div class="Submit">{{content}}</div>',
	// selected class
	'selectedClass' => 'Selected',
];


