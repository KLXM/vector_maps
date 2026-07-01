<?php

use KLXM\VectorMaps\Picker\PickerWidget;

class rex_yform_value_vector_map_location extends rex_yform_value_abstract
{
    public function enterObject(): void
    {
        $value = trim((string) $this->getValue());
        $required = (bool) $this->getElement('required');

        if ($this->params['send'] && $required && $value === '') {
            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = rex_i18n::msg('yform_values_required_msg');
        }

        $this->setValue($value);
        $this->params['value_pool']['email'][$this->getName()] = $value;
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $value;
        }

        if ($this->needsOutput() && $this->isViewable()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.vector_map_location.tpl.php', [
                'value' => $value,
                'map_style' => (string) $this->getElement('map_style'),
                'theme' => (string) $this->getElement('theme'),
                'picker_notice' => rex_i18n::msg('vector_maps_picker_notice'),
            ]);
        }
    }

    public function getDescription(): string
    {
        return 'vector_map_location|name|label|[map_style]|[theme]|[required]|[notice]';
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'vector_map_location',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'map_style' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('vector_maps_yform_map_style'),
                    'default' => 'liberty',
                    'notice' => 'liberty, bright, positron oder satellite',
                ],
                'theme' => [
                    'type' => 'text',
                    'label' => rex_i18n::msg('vector_maps_yform_theme'),
                    'notice' => 'Optional: dark, redaxo, bright, warm, mono oder eigener Theme-Name',
                ],
                'required' => [
                    'type' => 'boolean',
                    'label' => rex_i18n::msg('vector_maps_yform_required'),
                ],
                'notice' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('vector_maps_yform_location_description'),
            'db_type' => ['varchar(191)'],
            'famous' => false,
        ];
    }
}
