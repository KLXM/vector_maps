<?php

declare(strict_types=1);

namespace KLXM\VectorMaps\Picker;

use rex_form_base;
use rex_form_element;

final class PickerElement extends rex_form_element
{
    private ?PickerWidget $picker = null;

    public function __construct($tag = '', ?rex_form_base $table = null, array $attributes = [])
    {
        parent::__construct('', $table, $attributes);
    }

    public function setPickerWidget(): PickerWidget
    {
        if ($this->picker instanceof PickerWidget) {
            return $this->picker;
        }

        $name = (string) $this->getAttribute('name');
        $id = (string) $this->getAttribute('id');
        $this->picker = PickerWidget::factory($name, $id);

        return $this->picker;
    }

    public function formatElement(): string
    {
        $picker = $this->setPickerWidget();
        $picker->setValue((string) $this->getValue());

        return $picker->parse();
    }

    public function getSaveValue(): string
    {
        return (string) $this->getValue();
    }
}
