<?php

/**
 * @var rex_yform_value_showvalue $this
 * @psalm-scope-this rex_yform_value_showvalue
*/

$warningClass = str_replace('has-error', 'uk-form-danger', $this->getWarningClass());

$notice = [];
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
	$notice[] = '<div class="uk-margin-small-right ' . $warningClass . '">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</div>'; //	var_dump();
}
if ('' != $this->getElement('notice')) {
	$notice[] = '<div>' . rex_i18n::translate($this->getElement('notice'), false) . '</div>';
}
if (count($notice) > 0) {
	$notice = '<div class="uk-flex uk-flex-wrap uk-text-meta" uk-margin="margin: uk-margin-xsmall-top">' . implode('', $notice) . '</div>';
} else {
	$notice = '';
}

$class_group = str_replace('formshowvalue', 'uk-margin', $this->getHTMLClass());

?>
<div class="<?= $class_group ?>"  id="<?= $this->getHTMLId() ?>">
	<?php if ($this->getLabel() && preg_match('(uk-form-horizontal|uk-form-stacked)', $this->params['this']->getObjectparams('form_class')) === 1): ?>
	<label class="uk-form-label"><?= $this->getLabel() ?></label>
	<?php endif ?>
	<div class="uk-form-controls uk-form-controls-text">
		<span><?= (isset($showValue)) ? nl2br(rex_escape($showValue)) : rex_escape($this->getValue()) ?></span>
		<input type="hidden" name="<?= $this->getFieldName() ?>" value="<?= rex_escape($this->getValue()) ?>" />
		<?= $notice ?>
	</div>
</div>
