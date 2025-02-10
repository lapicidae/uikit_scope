<?php

/**
 * @var rex_yform_value_submit $this
 * @psalm-scope-this rex_yform_value_submit
*/

$labels ??= [];

$css_classes = [];
if ('' != $this->getElement('css_classes')) {
	$css_classes = explode(',', str_replace("btn-primary","uk-button-primary",$this->getElement('css_classes')));
}

if (count($labels) > 1) {
	// if (rex::isBackend()) {
	//	 echo '<div class="rex-form-panel-footer">';
	// }
	echo '<p uk-margin>';
}

foreach ($labels as $index => $label) {
	$classes = [];
	$classes[] = 'uk-button';
	// $classes[] = 'uk-button-primary';

	if (isset($css_classes[$index]) && '' != trim($css_classes[$index])) {
		$classes[] = trim($css_classes[$index]);
	}

	if ('' != $this->getWarningClass()) {
		$classes[] = $this->getWarningClass();
	}

	$id = $this->getFieldId() . '-' . rex_string::normalize($label);
	$label_translated = rex_i18n::translate($label, true);

	echo '<button class="' . implode(' ', $classes) . '" type="submit" name="' . $this->getFieldName() . '" id="' . $id . '" value="' . rex_escape($label) . '">' . $label_translated . '</button>' . PHP_EOL;
}

if (count($labels) > 1) {
	echo '</p>';
	// if (rex::isBackend()) {
	//	 echo '</div>';
	// }
}
