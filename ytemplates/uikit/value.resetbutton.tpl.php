<?php

/**
 * @var rex_yform_value_abstract $this
 * @psalm-scope-this rex_yform_value_abstract
*/

if ('' != trim($this->getElement(4))) {
    $css = $this->getElement(4);
} else {
	$css = 'uk-button-default';
}

echo '<button type="reset" class="uk-button '. $css . '" id="' . $this->getFieldId() . '" value="' . rex_escape($this->getValue()) . '">' . rex_escape(rex_i18n::translate($this->getValue())) . '</button>';
