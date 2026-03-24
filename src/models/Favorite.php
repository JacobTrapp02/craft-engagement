<?php

namespace jtdev\craftengagement\models;

use craft\base\Model;

/**
 * Read model exposed by the favorites field in Twig.
 */
class Favorite extends Model
{
    public bool $enabled = true;
    public ?int $id = null;
    public ?int $elementId = null;
    public ?int $fieldId = null;
    public ?int $siteId = null;
    public int $favoriteCount = 0;
    public bool $isFavorited = false;
    public string $icon = Settings::ICON_HEART;
    public string $emojiIcon = '⭐';
    public string $beforeFavoriteColor = '';
    public string $afterFavoriteColor = '';
    public ?string $customSvg = null;
    public bool $allowGuestInteractions = false;
    public ?string $headingText = null;
    public ?string $favoriteText = null;
    public ?string $unfavoriteText = null;

    public function rules(): array
    {
        return [
            [['enabled', 'isFavorited', 'allowGuestInteractions'], 'boolean'],
            [['id', 'elementId', 'fieldId', 'siteId', 'favoriteCount'], 'integer', 'min' => 0],
            [['icon', 'emojiIcon', 'beforeFavoriteColor', 'afterFavoriteColor', 'customSvg', 'headingText', 'favoriteText', 'unfavoriteText'], 'string'],
        ];
    }
}
