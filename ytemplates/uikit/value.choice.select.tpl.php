<?php

/**
 * @var rex_yform_value_abstract $this
 * @psalm-scope-this rex_yform_value_abstract
 * @var rex_yform_choice_list $choiceList
 * @var rex_yform_choice_list_view $choiceListView
*/

$warningClass = str_replace('has-error', 'uk-form-danger', $this->getWarningClass());

$notices = [];
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
	$notices[] = '<div class="uk-margin-small-right ' . $warningClass . '">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</div>';
}
if ($this->getElement('notice')) {
	$notices[] .= '<div>' . rex_i18n::translate($this->getElement('notice'), false) . '</div>';
}

if (!isset($groupAttributes)) {
	$groupAttributes = [];
}

$groupClass = trim('uk-margin ' . $this->getWarningClass());
if (isset($groupAttributes['class']) && is_array($groupAttributes['class'])) {
	$groupAttributes['class'][] = $groupClass;
} elseif (isset($groupAttributes['class'])) {
	$groupAttributes['class'] .= ' ' . $groupClass;
} else {
	$groupAttributes['class'] = $groupClass;
}

if (!isset($elementAttributes)) {
	$elementAttributes = [];
}

$elementClass = 'uk-select';
if (!empty($this->getWarningClass())) {
	$elementClass .= ' ' . $warningClass;
}

if (isset($this->params['fixdata'][$this->getName()]) && !isset($elementAttributes['disabled'])) {
	$elementAttributes['disabled'] = 'disabled';
}

if (isset($elementAttributes['class']) && is_array($elementAttributes['class'])) {
	$elementAttributes['class'][] = $elementClass;
} elseif (isset($elementAttributes['class'])) {
	$elementAttributes['class'] .= ' ' . $elementClass;
} else {
	$elementAttributes['class'] = $elementClass;
}

if ($this->getLabel() && !preg_grep('/^aria-label=/', $elementAttributes)) {
	$elementAttributes[] = trim('aria-label=' . $this->getLabel());
}

if ($choiceList->getPlaceholder()) {
	$placeholder = rex_escape($choiceList->getPlaceholder());
} elseif ($this->getLabel() && preg_match('(uk-form-horizontal|uk-form-stacked)', $this->params['this']->getObjectparams('form_class')) === 0){
	$placeholder = rex_escape($this->getLabel());
}
?>

<?php $choiceOutput = function (rex_yform_choice_view $view) {
	?>
	<option
		value="<?= rex_escape($view->getValue()) ?>"
		<?= in_array($view->getValue(), $this->getValue(), true) ? ' selected="selected"' : '' ?>
		<?= $view->getAttributesAsString() ?>
	>
		<?= $view->getLabel() ?>
	</option>
<?php
} ?>

<?php $choiceGroupOutput = static function (rex_yform_choice_group_view $view) use ($choiceOutput) {
		?>
	<optgroup label="<?= rex_escape($view->getLabel()) ?>">
		<?php foreach ($view->getChoices() as $choiceView): ?>
			<?php $choiceOutput($choiceView) ?>
		<?php endforeach ?>
	</optgroup>
<?php
	} ?>

<?php
	if (!isset($groupAttributes['id'])) {
		$groupAttributes['id'] = $this->getHTMLId();
	}

	// RexSelectStyle im Backend nutzen
	//$useRexSelectStyle = rex::isBackend();

	// // RexSelectStyle nicht nutzen, wenn die Klasse `.selectpicker` gesetzt ist
	// if (isset($elementAttributes['class']) && str_contains($elementAttributes['class'], 'selectpicker')) {
	// 	$useRexSelectStyle = false;
	// }
	// // RexSelectStyle nicht nutzen, wenn das Selectfeld mehrzeilig ist
	// if (isset($elementAttributes['size']) && (int) $elementAttributes['size'] > 1) {
	// 	$useRexSelectStyle = false;
	// }
	$useRexSelectStyle = $useRexSelectStyle ?? false;
 ?>
<div<?= rex_string::buildAttributes($groupAttributes) ?>>
	<?php if ($this->getLabel()): ?>
		<?php if (preg_match('(uk-form-horizontal|uk-form-stacked)', $this->params['this']->getObjectparams('form_class')) === 1): ?>
		<label class="uk-form-label" for="<?= $this->getFieldId() ?>">
			<?= rex_escape($this->getLabelStyle($this->getLabel())) ?>
		</label>
		<?php endif ?>
	<?php endif ?>


	<?php if ($useRexSelectStyle): ?>
	<div class="rex-select-style">
	<?php endif ?>
	<div class="uk-form-controls">
		<select<?= rex_string::buildAttributes($elementAttributes) ?>>
			<?php if (isset($placeholder) && !$choiceList->isMultiple()): ?>
				<option value=""><?= $placeholder ?></option>
			<?php endif ?>

			<?php foreach ($choiceListView->getPreferredChoices() as $view): ?>
				<?php $view instanceof rex_yform_choice_group_view ? $choiceGroupOutput($view) : $choiceOutput($view) ?>
			<?php endforeach ?>

			<?php if ($choiceListView->getPreferredChoices()): ?>
				<option disabled="disabled">-------------------</option>
			<?php endif ?>

			<?php foreach ($choiceListView->getChoices() as $view): ?>
				<?php $view instanceof rex_yform_choice_group_view ? $choiceGroupOutput($view) : $choiceOutput($view) ?>
			<?php endforeach ?>
		</select>
		<?php if ($notices): ?>
		<div class="uk-flex uk-text-meta uk-flex-wrap" uk-margin="margin: uk-margin-xsmall-top"><?= implode('', $notices) ?></div>
		<?php endif ?>
	</div>
	<?php if ($useRexSelectStyle): ?>
	</div>
	<?php endif ?>
</div>
