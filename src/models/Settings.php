<?php

namespace jtdev\craftengagement\models;

use craft\base\Model;

/**
 * Engagement settings.
 */
class Settings extends Model
{
    public const ICON_STAR = 'star';
    public const ICON_HEART = 'heart';
    public const ICON_THUMBS = 'thumbs';
    public const ICON_CUSTOM_SVG = 'customSvg';

    public int $maxScale = 10;
    public int $defaultScale = 5;
    public string $defaultIcon = self::ICON_STAR;
    public bool $moderationEnabled = false;

    /**
     * @var int[]
     */
    public array $moderatorGroups = [];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            [['maxScale', 'defaultScale'], 'required'],
            [['maxScale', 'defaultScale'], 'integer', 'min' => 1, 'max' => 100],
            [['defaultScale'], 'compare', 'compareAttribute' => 'maxScale', 'operator' => '<='],
            [['defaultIcon'], 'required'],
            [['defaultIcon'], 'in', 'range' => [
                self::ICON_STAR,
                self::ICON_HEART,
                self::ICON_THUMBS,
                self::ICON_CUSTOM_SVG,
            ]],
            [['moderationEnabled'], 'boolean'],
            [['moderatorGroups'], 'default', 'value' => []],
            [['moderatorGroups'], 'each', 'rule' => ['integer', 'min' => 1]],
        ];
    }

    public function beforeValidate(): bool
    {
        $this->moderatorGroups = array_values(array_unique(array_map('intval', $this->moderatorGroups)));

        return parent::beforeValidate();
    }
}
