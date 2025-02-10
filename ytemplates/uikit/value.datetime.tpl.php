<?php

/**
 * @var rex_yform_value_abstract $this
 * @psalm-scope-this rex_yform_value_abstract
*/

$warningClass = str_replace('has-error', 'uk-form-danger', $this->getWarningClass());

$second ??= 0;
$minute ??= 0;
$hour ??= 0;
$day ??= 0;
$month ??= 0;
$year ??= 0;
$format = ($this->getElement('format') == '') ? 'YYYY-MM-DD HH:ii:ss' : $this->getElement('format');
// $format ??= 'YYYY-MM-DD HH:ii:ss';
// $yearStart = ($this->getElement('year_start') == '') ? '1800' : $this->getElement('year_start');
$yearStart ??= '1800';
// $yearEnd = ($this->getElement('year_end') == '') ? '2100' : $this->getElement('year_end');
$yearEnd ??= '2100';

$notices = [];
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
	$notices[] = '<div class="uk-margin-small-right ' . $warningClass . '">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</div>';
}
if ($this->getElement('notice')) {
	$notices[] .= '<div>' . rex_i18n::translate($this->getElement('notice'), false) . '</div>';
}

$notice = '';
if (count($notices) > 0) {
	$notice = '<div class="uk-flex uk-flex-wrap uk-text-meta" uk-margin="margin: uk-margin-xsmall-top">' . implode('', $notices) . '</div>';
}

$class_group = 'uk-margin';
$class_label[] = 'uk-form-label';

$output = preg_replace('/[^a-z\d]/i', '', $format);

$search = [];
$replace = [];

$pos = strpos($format, 'YYYY');
if (false !== $pos) {
	$attributes = $this->getAttributeElements([
		'class' => trim('uk-select ' . $warningClass),
		'id' => $this->getFieldId('year'),
		'name' => $this->getFieldName() . '[year]',
		'aria-label' => rex_i18n::msg('uikit_scope_year'),
	], ['required', 'disabled', 'readonly']);

	$replace_i = '<select ' . implode(' ', $attributes) . '>';
	$replace_i .= '<option value="00">--</option>';
	for ($i = $yearStart; $i <= $yearEnd; ++$i):
		$selected = (@$year == $i) ? ' selected="selected"' : '';
		$replace_i .= '<option value="' . $i . '"' . $selected . '>' . str_pad($i, 4, '0', STR_PAD_LEFT) . '</option>';
	endfor;
	$replace_i .= '</select>';
	$replace['YYYY'] = $replace_i;
	$search[] = 'YYYY';
}

$pos = strpos($format, 'MM');
if (false !== $pos) {
	$attributes = $this->getAttributeElements([
		'class' => trim('uk-select ' . $warningClass),
		'id' => $this->getFieldId('month'),
		'name' => $this->getFieldName() . '[month]',
		'aria-label' => rex_i18n::msg('uikit_scope_month'),
	], ['required', 'disabled', 'readonly']);

	$replace_i = '<select ' . implode(' ', $attributes) . '>';
	$replace_i .= '<option value="00">--</option>';
	for ($i = 1; $i < 13; ++$i):
		$selected = (@$month == $i) ? ' selected="selected"' : '';
		$replace_i .= '<option value="' . $i . '"' . $selected . '>' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '</option>';
	endfor;
	$replace_i .= '</select>';
	$replace['MM'] = $replace_i;
	$search[] = 'MM';
}

$pos = strpos($format, 'DD');
if (false !== $pos) {
	$attributes = $this->getAttributeElements([
		'class' => trim('uk-select ' . $warningClass),
		'id' => $this->getFieldId('day'),
		'name' => $this->getFieldName() . '[day]',
		'aria-label' => rex_i18n::msg('uikit_scope_day'),
	], ['required', 'disabled', 'readonly']);

	$replace_i = '<select ' . implode(' ', $attributes) . '>';
	$replace_i .= '<option value="00">--</option>';
	for ($i = 1; $i < 32; ++$i):
		$selected = (@$day == $i) ? ' selected="selected"' : '';
		$replace_i .= '<option value="' . $i . '"' . $selected . '>' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '</option>';
	endfor;
	$replace_i .= '</select>';
	$replace['DD'] = $replace_i;
	$search[] = 'DD';
}

$pos = strpos($format, 'HH');
if (false !== $pos) {
	$attributes = $this->getAttributeElements([
		'class' => trim('uk-select ' . $warningClass),
		'id' => $this->getFieldId('hour'),
		'name' => $this->getFieldName() . '[hour]',
		'aria-label' => rex_i18n::msg('uikit_scope_hour'),
	], ['required', 'disabled', 'readonly']);

	$replace_i = '<select ' . implode(' ', $attributes) . '>';
	for ($i = 0; $i < 24; ++$i) {
		$selected = (@$hour == $i) ? ' selected="selected"' : '';
		$replace_i .= '<option value="' . $i . '" ' . $selected . '>' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '</option>';
	}
	$replace_i .= '</select>';
	$replace['HH'] = $replace_i;
	$search[] = 'HH';
}

$pos = strpos($format, 'ii');
if (false !== $pos) {
	$attributes = $this->getAttributeElements([
		'class' => trim('uk-select ' . $warningClass),
		'id' => $this->getFieldId('minute'),
		'name' => $this->getFieldName() . '[minute]',
		'aria-label' => rex_i18n::msg('uikit_scope_minute'),
	], ['required', 'disabled', 'readonly']);

	$replace_i = '<select ' . implode(' ', $attributes) . '>';
	for ($i = 0; $i < 60; ++$i) {
		$selected = (@$minute == $i) ? ' selected="selected"' : '';
		$replace_i .= '<option value="' . $i . '" ' . $selected . '>' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '</option>';
	}
	$replace_i .= '</select>';
	$replace['ii'] = $replace_i;
	$search[] = 'ii';
}

$pos = strpos($format, 'ss');
if (false !== $pos) {
	$attributes = $this->getAttributeElements([
		'class' => trim('uk-select ' . $warningClass),
		'id' => $this->getFieldId('second'),
		'name' => $this->getFieldName() . '[second]',
		'aria-label' => rex_i18n::msg('uikit_scope_second'),
	], ['required', 'disabled', 'readonly']);

	$replace_i = '<select ' . implode(' ', $attributes) . '>';
	for ($i = 0; $i < 60; ++$i) {
		$selected = (@$second == $i) ? ' selected="selected"' : '';
		$replace_i .= '<option value="' . $i . '"' . $selected . '>' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '</option>';
	}
	$replace_i .= '</select>';
	$replace['ss'] = $replace_i;
	$search[] = 'ss';
}

// $output = str_replace($search, $replace, $output);
$output = strtr($output, $replace);
?>

<div class="<?= $class_group ?>" id="<?= $this->getHTMLId() ?>">
	<?php if ($this->getLabel() && preg_match('(uk-form-horizontal|uk-form-stacked)', $this->params['this']->getObjectparams('form_class')) === 1): ?>
	<label class="<?= implode(' ', $class_label) ?>" for="<?= $this->getFieldId() ?>"><?= $this->getLabel() ?></label>
	<? endif ?>
	<div class="uk-form-controls">
		<div class="uk-flex-inline uk-flex-row uk-flex-middle uk-flex-wrap uk-child-width-auto" uk-margin><?= $output ?></div>
		<?= $notice ?>
	</div>
</div>
