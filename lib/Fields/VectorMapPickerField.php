<?php

declare(strict_types=1);

namespace KLXM\VectorMaps\Fields;

use FriendsOfREDAXO\Builder\Fields\FieldAbstract;
use KLXM\VectorMaps\Picker\PickerWidget;

final class VectorMapPickerField extends FieldAbstract
{
    public static function getType(): string
    {
        return 'vector_map_picker';
    }

    public function render(string $fieldName, array $fieldConfig, mixed $value, array $sliceData = []): void
    {
        if (!$this->hasPermission($fieldConfig)) {
            return;
        }

        $label = (string) ($fieldConfig['label'] ?? $fieldName);
        $placeholder = (string) ($fieldConfig['placeholder'] ?? '52.520008,13.404954');
        $notice = isset($fieldConfig['notice']) ? (string) $fieldConfig['notice'] : null;

        $mapStyleField = (string) ($fieldConfig['map_style_field'] ?? 'map_style');
        $themeField = (string) ($fieldConfig['theme_field'] ?? 'theme');
        $mapStyle = isset($sliceData[$mapStyleField]) ? (string) $sliceData[$mapStyleField] : 'liberty';
        $theme = isset($sliceData[$themeField]) ? (string) $sliceData[$themeField] : '';

        $picker = PickerWidget::factory($fieldName)
            ->setValue((string) $value)
            ->setPlaceholder($placeholder)
            ->setMapStyle($mapStyle)
            ->setTheme($theme);

        $this->openFormGroup();
        $this->renderLabel($label);
        echo $picker->parse();
        $this->closeFormGroup($notice);
    }
}
