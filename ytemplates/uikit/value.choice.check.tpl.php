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

$groupClass = 'uk-margin';
if (isset($groupAttributes['class']) && is_array($groupAttributes['class'])) {
	$groupAttributes['class'][] = $groupClass;
} elseif (isset($groupAttributes['class'])) {
	$groupAttributes['class'] .= ' ' . $groupClass;
} else {
	$groupAttributes['class'] = $groupClass;
}

$flexGroup = $flexGroup ?? false;
foreach ($choiceListView->getChoices() as $view) {
	if ($view instanceof rex_yform_choice_group_view) {
		$flexGroup = true;
		break;
	}
}

if (!isset($elementAttributes)) {
	$elementAttributes = [];
}
$elementClass =	'uk-flex uk-flex-column';
if ($flexGroup) {
	$elementClass .= ' uk-margin-xsmall-left';
}
// $elementClass = trim($elementClass . $warningClass);
if (isset($elementAttributes['class']) && is_array($elementAttributes['class'])) {
	$elementAttributes['class'][] = $elementClass;
} elseif (isset($elementAttributes['class'])) {
	$elementAttributes['class'] .= ' ' . $elementClass;
} else {
	$elementAttributes['class'] = $elementClass;
}
$inputClass = trim(($choiceList->isMultiple() ? 'uk-checkbox' : 'uk-radio') . ' uk-margin-remove-top uk-flex-none uk-margin-xsmall-right');
?>

<?php $choiceOutput = function (rex_yform_choice_view $view) use ($elementAttributes, $inputClass) {
	?>
	<div<?= rex_string::buildAttributes($elementAttributes) ?>>
		<span class="uk-flex-inline uk-flex-middle">
			<input
				class="<?= $inputClass ?>"
				value="<?= rex_escape($view->getValue()) ?>"
				<?= in_array($view->getValue(), $this->getValue(), true) ? ' checked="checked"' : '' ?>
				<?= $view->getAttributesAsString() ?>
			/>
			<label for="<?= $view->getAttributes()['id'] ?>"><?= $view->getLabel() ?></label>
		</span>
	</div>
<?php } ?>

<?php $choiceGroupOutput = static function (rex_yform_choice_group_view $view) use ($choiceOutput) {
	?>
	<div class="uk-margin-right">
		<span><?= rex_escape($view->getLabel()) ?></span>
		<?php foreach ($view->getChoices() as $choiceView): ?>
			<?php $choiceOutput($choiceView) ?>
		<?php endforeach ?>
	</div>
<?php } ?>

<?php
	if (!isset($groupAttributes['id'])) {
		$groupAttributes['id'] = $this->getHTMLId();
	}
 ?>

<div<?= rex_string::buildAttributes($groupAttributes) ?>>
	<?php if ($this->getLabel() && preg_match('(uk-form-horizontal|uk-form-stacked)', $this->params['this']->getObjectparams('form_class')) === 1): ?>
		<label class="uk-form-label" for="<?= $this->getFieldId() ?>">
			<?= rex_escape($this->getLabelStyle($this->getLabel())) ?>
		</label>
	<?php endif ?>

	<?php if ($flexGroup): ?>
		<div class="<?= trim('uk-form-controls uk-form-controls-text uk-flex uk-flex-wrap ' . $warningClass) ?>" uk-margin>
	<?php else: ?>
		<div class="<?= trim('uk-form-controls uk-form-controls-text ' . $warningClass) ?>">
	<?php endif ?>

	<?php foreach ($choiceListView->getPreferredChoices() as $view): ?>
		<?php $view instanceof rex_yform_choice_group_view ? $choiceGroupOutput($view) : $choiceOutput($view) ?>
	<?php endforeach ?>

	<?php foreach ($choiceListView->getChoices() as $view): ?>
		<?php $view instanceof rex_yform_choice_group_view ? $choiceGroupOutput($view) : $choiceOutput($view) ?>
	<?php endforeach ?>

		</div>

	<?php if ($notices): ?>
		<div class="uk-form-controls uk-flex uk-flex-wrap uk-text-meta uk-margin-xsmall-top" uk-margin="margin: uk-margin-xsmall-top"><?= implode('', $notices) ?></div>
	<?php endif ?>
</div>
