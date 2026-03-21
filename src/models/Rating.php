<?php

namespace jtdev\craftengagement\models;

use craft\base\Model;

/**
 * Read model exposed by the rating field in Twig.
 */
class Rating extends Model
{
    public bool $enabled = true;
    public ?int $id = null;
    public ?int $elementId = null;
    public ?int $fieldId = null;
    public ?int $siteId = null;
    public float $average = 0.0;
    public int $voteCount = 0;
    public int $scale = 5;
    public string $icon = Settings::ICON_STAR;
    public ?string $customSvg = null;

    public function rules(): array
    {
        return [
            [['enabled'], 'boolean'],
            [['id', 'elementId', 'fieldId', 'siteId', 'voteCount', 'scale'], 'integer', 'min' => 0],
            [['average'], 'number', 'min' => 0],
            [['scale'], 'integer', 'min' => 1, 'max' => 100],
            [['icon'], 'in', 'range' => [
                Settings::ICON_STAR,
                Settings::ICON_HEART,
                Settings::ICON_THUMBS,
                Settings::ICON_CUSTOM_SVG,
            ]],
            [['customSvg'], 'string'],
        ];
    }

    public function getPercentage(): float
    {
        if ($this->scale <= 0) {
            return 0.0;
        }

        return ($this->average / $this->scale) * 100;
    }

    public function getRoundedAverage(): float
    {
        return round($this->average, 1);
    }
}
