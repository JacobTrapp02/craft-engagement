<?php

namespace jtdev\craftengagement\models;

use craft\base\Model;

/**
 * Read model exposed by the favorites field in Twig.
 */
class Favorite extends Model
{
    public const DISPLAY_PUBLIC = 'public';
    public const DISPLAY_PERSONAL = 'personal';

    public bool $enabled = true;
    public ?int $id = null;
    public ?int $elementId = null;
    public ?int $fieldId = null;
    public ?int $siteId = null;
    public int $favoriteCount = 0;
    public bool $isFavorited = false;
    public string $displayMode = self::DISPLAY_PUBLIC;
    public bool $showPublicCount = true;
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
            [['enabled', 'isFavorited', 'showPublicCount', 'allowGuestInteractions'], 'boolean'],
            [['id', 'elementId', 'fieldId', 'siteId', 'favoriteCount'], 'integer', 'min' => 0],
            [['displayMode'], 'in', 'range' => [self::DISPLAY_PUBLIC, self::DISPLAY_PERSONAL]],
            [['icon', 'emojiIcon', 'beforeFavoriteColor', 'afterFavoriteColor', 'customSvg', 'headingText', 'favoriteText', 'unfavoriteText'], 'string'],
        ];
    }
}
