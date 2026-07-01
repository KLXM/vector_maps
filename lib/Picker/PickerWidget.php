<?php

declare(strict_types=1);

namespace KLXM\VectorMaps\Picker;

use KLXM\VectorMaps\ThemeManager;
use rex_escape;
use rex_i18n;
use rex_string;

final class PickerWidget
{
    private string $name;
    private string $id;
    private string $value = '';
    private string $placeholder = '';
    private string $mapStyle = 'liberty';
    private string $theme = '';
    /** @var array<string, string> */
    private array $themeChoices = [];
    /** @var array<string, scalar> */
    private array $attributes = [];
    /** @var array<string> */
    private array $classes = ['form-control'];
    private bool $required = false;

    private function __construct(string $name, string $id)
    {
        $this->name = $name;
        $this->id = $id;
        $this->placeholder = rex_i18n::msg('vector_maps_picker_placeholder');
        $this->themeChoices = ThemeManager::getThemeChoices(false);
    }

    public static function factory(string $name, string $id = ''): self
    {
        $normalizedId = trim($id) !== '' ? trim($id) : uniqid('vm-picker-', false);
        return new self($name, $normalizedId);
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function setPlaceholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function setMapStyle(string $mapStyle): self
    {
        $this->mapStyle = trim($mapStyle) !== '' ? trim($mapStyle) : 'liberty';
        return $this;
    }

    public function setTheme(string $theme): self
    {
        $this->theme = trim($theme);
        return $this;
    }

    /**
     * @param array<string, string> $themeChoices
     */
    public function setThemeChoices(array $themeChoices): self
    {
        $this->themeChoices = $themeChoices;
        return $this;
    }

    /**
     * @param array<string, scalar> $attributes
     */
    public function setAttributes(array $attributes): self
    {
        foreach ($attributes as $name => $value) {
            $key = trim((string) $name);
            if ($key === '') {
                continue;
            }
            $this->attributes[$key] = $value;
        }

        return $this;
    }

    public function addClass(string $className): self
    {
        foreach (preg_split('/\s+/', trim($className)) ?: [] as $class) {
            if ($class !== '') {
                $this->classes[] = $class;
            }
        }

        return $this;
    }

    public function setRequired(bool $required = true): self
    {
        $this->required = $required;
        return $this;
    }

    public function parse(): string
    {
        $attributes = $this->attributes;
        $attributes['type'] = 'text';
        $attributes['id'] = $this->id;
        $attributes['name'] = $this->name;
        $attributes['value'] = $this->value;
        $attributes['class'] = implode(' ', array_values(array_unique($this->classes)));
        $attributes['autocomplete'] = 'off';
        $attributes['placeholder'] = $this->placeholder;
        $attributes['data-vector-picker'] = '1';
        $attributes['data-vector-picker-style'] = $this->mapStyle;
        $attributes['data-vector-picker-theme'] = $this->theme;
        $attributes['data-vector-picker-themes'] = json_encode($this->themeChoices, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($this->required) {
            $attributes['required'] = 'required';
        }

        return '<input' . rex_string::buildAttributes($attributes) . ' />';
    }
}
