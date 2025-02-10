<?php

/**
 * @var rex_yform_value_textarea $this
 * @psalm-scope-this rex_yform_value_textarea
*/

$warningClass = str_replace('has-error', 'uk-form-danger', $this->getWarningClass());

$notice = [];
$class_notice = 'uk-flex uk-flex-wrap uk-text-meta';
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
	$notice[] = '<div class="uk-margin-small-right ' . $warningClass . '">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()]) . '</div>'; //	var_dump();
}
if ('' != $this->getElement('notice')) {
	$notice[] = '<div>' . rex_i18n::translate($this->getElement('notice'), false) . '</div>';
}
if (count($notice) > 0) {
	$notice = '<div class="' . $class_notice . '" uk-margin="margin: uk-margin-xsmall-top">' . implode('', $notice) . '</div>';
} else {
	$notice = '';
}

$class = $this->getElement('required') ? 'form-is-required ' : '';
$class_group = 'uk-margin';
$class_label = ['uk-form-label'];

$rows = $this->getElement('rows');
if (!$rows) {
	$rows = 10;
}

$attributes = [
	'class' => 'uk-textarea',
	'name' => $this->getFieldName(),
	'id' => $this->getFieldId(),
	'rows' => $rows,
	'aria-label' => $this->getLabel(),
];

if (!empty($this->getWarningClass())) {
	$attributes['class'] .= ' ' . $warningClass;
}

$attributes = $this->getAttributeElements($attributes, ['placeholder', 'pattern', 'required', 'disabled', 'readonly']);

if (preg_match('(uk-form-horizontal|uk-form-stacked)', $this->params['this']->getObjectparams('form_class')) === 1) {

	echo '<div class="' . $class_group . '" id="' . $this->getHTMLId() . '">
			<label class="' . implode(' ', $class_label) . '" for="' . $this->getFieldId() . '">' . $this->getLabel() . '</label>
			<div class="uk-form-controls"><textarea ' . implode(' ', $attributes) . '>' . rex_escape($this->getValue()) . '</textarea>
			' . $notice . '
			</div></div>';

} else {

	if (!preg_grep('/^placeholder=/', $attributes)) {
		$attributes[] = 'placeholder=' . $this->getLabel();
	}

	echo '<div class="' . $class_group . '" id="' . $this->getHTMLId() . '">
			<textarea ' . implode(' ', $attributes) . '>' . rex_escape($this->getValue()) . '</textarea>
			' . $notice . '
			</div>';

}
