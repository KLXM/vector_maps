<?php

use KLXM\VectorMaps\Picker\PickerWidget;

/** @var rex_yform_value_vector_map_location $this */

$pickerNotice = isset($picker_notice) ? (string) $picker_notice : '';

$wrapperClass = 'form-group';
if ($this->getWarningClass() !== '') {
    $wrapperClass .= ' ' . $this->getWarningClass();
}

$widget = PickerWidget::factory($this->getFieldName(), $this->getFieldId())
    ->setValue((string) ($value ?? ''))
    ->setMapStyle((string) ($map_style ?? 'liberty'))
    ->setTheme((string) ($theme ?? ''))
    ->setRequired((bool) $this->getElement('required'));

$warningText = '';
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $warningText = (string) $this->params['warning_messages'][$this->getId()];
}
?>
<div class="<?= rex_escape($wrapperClass) ?>" id="<?= rex_escape($this->getHTMLId()) ?>">
    <label class="control-label" for="<?= rex_escape($this->getFieldId()) ?>"><?= rex_i18n::translate($this->getElement('label')) ?></label>
    <?= $widget->parse() ?>
    <?php if ($warningText !== ''): ?>
        <p class="help-block small"><span class="text-warning"><?= rex_escape(rex_i18n::translate($warningText)) ?></span></p>
    <?php endif; ?>
    <?php if ($pickerNotice !== ''): ?>
        <p class="help-block"><?= rex_escape($pickerNotice) ?></p>
    <?php endif; ?>
    <?php if ($this->getElement('notice') !== ''): ?>
        <p class="help-block"><?= rex_i18n::translate($this->getElement('notice'), false) ?></p>
    <?php endif; ?>
</div>
