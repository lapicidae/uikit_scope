<?php

/**
 * @var rex_yform_value_checkbox $this
 * @psalm-scope-this rex_yform_value_checkbox
*/

$value ??= $this->getValue() ?? '';

$warningClass = str_replace('has-error', 'uk-form-danger', $this->getWarningClass());

$notices = [];
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
	$notices[] = '<div class="uk-margin-small-right ' . $warningClass . '">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</div>';
}
if ($this->getElement('notice')) {
	$notices[] .= '<div>' . rex_i18n::translate($this->getElement('notice'), false) . '</div>';
}

$notice = '';
if (count($notices) > 0) {
	$notice = '<div class="uk-flex uk-flex-wrap uk-text-meta uk-margin-left" uk-margin="margin: uk-margin-xsmall-top">' . implode('', $notices) . '</div>';
}

$htmlClass = str_replace('formcheckbox', '', $this->getHTMLClass());
$class_group = trim('uk-margin ' . $htmlClass . ' ' . $warningClass);

$attributes = [
	'class' => 'uk-checkbox uk-margin-remove-top uk-flex-none uk-margin-xsmall-right',
	'type' => 'checkbox',
	'id' => $this->getFieldId(),
	'name' => $this->getFieldName(),
	'value' => 1,
];
if (1 == $value) {
	$attributes['checked'] = 'checked';
}

$attributes = $this->getAttributeElements($attributes, ['required', 'disabled', 'autofocus']);

?>

<div class="<?= $class_group ?>" id="<?= $this->getHTMLId() ?>">
	<div class="uk-form-controls uk-form-controls-text">
		<div class="uk-flex uk-flex-column">
			<span class="uk-flex-inline uk-flex-middle">
				<input <?= implode(' ', $attributes) ?>>
				<label for="<?= $this->getFieldId() ?>"><?= $this->getLabel() ?></label>
			</span>
			<?= $notice ?>
		</div>
	</div>
</div>
