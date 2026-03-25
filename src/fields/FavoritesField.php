<?php

namespace jtdev\craftengagement\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Json;
use jtdev\craftengagement\models\Favorite;
use jtdev\craftengagement\models\Settings;
use jtdev\craftengagement\Plugin;
use yii\db\Schema;

/**
 * Favorites field.
 */
class FavoritesField extends Field
{
    public const ICON_EMOJI = 'emoji';
    public const ICON_NONE = 'none';

    /**
     * @deprecated Back-compat for older saved field configs.
     */
    public ?bool $allowEntryOverrides = null;

    /**
     * @deprecated Back-compat for older saved field configs.
     */
    public ?bool $allowEntryTextOverrides = null;

    /**
     * @deprecated Back-compat for older saved field configs.
     */
    public ?bool $allowOverrideDisplayMode = null;

    /**
     * @deprecated Back-compat for older saved field configs.
     */
    public ?string $displayMode = null;

    public bool $defaultEnabled = true;
    public bool $allowEditorOverrides = true;
    public bool $allowOverrideIconAppearance = true;
    public bool $allowOverrideIconColors = true;
    public bool $allowOverrideWidgetEnabled = true;
    public bool $allowOverrideWidgetPreview = true;
    public bool $allowOverrideGuestInteractions = true;
    public bool $allowOverrideHeadingText = true;
    public bool $allowOverrideFavoriteText = true;
    public bool $allowOverrideUnfavoriteText = true;
    public string $icon = Settings::ICON_HEART;
    public string $emojiIcon = '⭐';
    public string $beforeFavoriteColor = '';
    public string $afterFavoriteColor = '';
    public bool $allowGuestInteractions = false;
    public ?string $customSvg = null;
    public ?string $headingText = null;
    public ?string $favoriteText = null;
    public ?string $unfavoriteText = null;

    public static function displayName(): string
    {
        return Craft::t('engagement', 'Favorites');
    }

    public static function icon(): string
    {
        return 'heart';
    }

    public static function dbType(): ?string
    {
        return Schema::TYPE_TEXT;
    }

    public function init(): void
    {
        parent::init();

        if ($this->allowEntryOverrides !== null) {
            $this->allowEditorOverrides = $this->allowEntryOverrides;
        }

        if ($this->allowEntryTextOverrides !== null) {
            $this->allowOverrideHeadingText = $this->allowEntryTextOverrides;
            $this->allowOverrideFavoriteText = $this->allowEntryTextOverrides;
            $this->allowOverrideUnfavoriteText = $this->allowEntryTextOverrides;
        }

        if ($this->icon === '') {
            $this->icon = Settings::ICON_HEART;
        }
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['icon'], 'required'];
        $rules[] = [['icon'], 'in', 'range' => [
            Settings::ICON_HEART,
            Settings::ICON_STAR,
            Settings::ICON_CUSTOM_SVG,
            self::ICON_EMOJI,
            self::ICON_NONE,
        ]];
        $rules[] = [[
            'defaultEnabled',
            'allowEntryOverrides',
            'allowEntryTextOverrides',
            'allowEditorOverrides',
            'allowOverrideIconAppearance',
            'allowOverrideIconColors',
            'allowOverrideWidgetEnabled',
            'allowOverrideWidgetPreview',
            'allowOverrideGuestInteractions',
            'allowOverrideHeadingText',
            'allowOverrideFavoriteText',
            'allowOverrideUnfavoriteText',
        ], 'boolean'];
        $rules[] = [['allowGuestInteractions'], 'boolean'];
        $rules[] = [['emojiIcon', 'beforeFavoriteColor', 'afterFavoriteColor', 'customSvg', 'headingText', 'favoriteText', 'unfavoriteText'], 'string'];
        $rules[] = [['customSvg'], 'required', 'when' => function(): bool {
            return $this->icon === Settings::ICON_CUSTOM_SVG;
        }];

        return $rules;
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'engagement/favorites/_fields/admin.twig',
            [
                'field' => $this,
                'iconOptions' => $this->iconOptions(),
            ]
        );
    }

    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $favorite = $value instanceof Favorite ? $value : $this->normalizeValue($value, $element);

        return Json::encode([
            'enabled' => (bool)$favorite->enabled,
            'icon' => (string)$favorite->icon,
            'emojiIcon' => (string)$favorite->emojiIcon,
            'beforeFavoriteColor' => (string)$favorite->beforeFavoriteColor,
            'afterFavoriteColor' => (string)$favorite->afterFavoriteColor,
            'customSvg' => $favorite->customSvg,
            'allowGuestInteractions' => (bool)$favorite->allowGuestInteractions,
            'headingText' => $favorite->headingText,
            'favoriteText' => $favorite->favoriteText,
            'unfavoriteText' => $favorite->unfavoriteText,
        ]);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $stored = $this->normalizeStoredValue($value);

        $enabled = ($this->allowEditorOverrides && $this->allowOverrideWidgetEnabled)
            ? ($stored['enabled'] ?? $this->defaultEnabled)
            : $this->defaultEnabled;
        $icon = ($this->allowEditorOverrides && $this->allowOverrideIconAppearance)
            ? ($stored['icon'] ?? $this->icon)
            : $this->icon;
        $emojiIcon = ($this->allowEditorOverrides && $this->allowOverrideIconAppearance)
            ? ($stored['emojiIcon'] ?? $this->emojiIcon)
            : $this->emojiIcon;
        $beforeFavoriteColor = ($this->allowEditorOverrides && $this->allowOverrideIconColors)
            ? ($stored['beforeFavoriteColor'] ?? $this->beforeFavoriteColor)
            : $this->beforeFavoriteColor;
        $afterFavoriteColor = ($this->allowEditorOverrides && $this->allowOverrideIconColors)
            ? ($stored['afterFavoriteColor'] ?? $this->afterFavoriteColor)
            : $this->afterFavoriteColor;
        $customSvg = ($this->allowEditorOverrides && $this->allowOverrideIconAppearance)
            ? ($stored['customSvg'] ?? $this->customSvg)
            : $this->customSvg;
        $allowGuestInteractions = ($this->allowEditorOverrides && $this->allowOverrideGuestInteractions)
            ? ($stored['allowGuestInteractions'] ?? $this->allowGuestInteractions)
            : $this->allowGuestInteractions;
        $headingText = ($this->allowEditorOverrides && $this->allowOverrideHeadingText)
            ? ($stored['headingText'] ?? $this->headingText)
            : $this->headingText;
        $favoriteText = ($this->allowEditorOverrides && $this->allowOverrideFavoriteText)
            ? ($stored['favoriteText'] ?? $this->favoriteText)
            : $this->favoriteText;
        $unfavoriteText = ($this->allowEditorOverrides && $this->allowOverrideUnfavoriteText)
            ? ($stored['unfavoriteText'] ?? $this->unfavoriteText)
            : $this->unfavoriteText;

        if ($element === null || !$element->id) {
            return new Favorite([
                'enabled' => $enabled,
                'icon' => $icon,
                'emojiIcon' => $emojiIcon,
                'beforeFavoriteColor' => $beforeFavoriteColor,
                'afterFavoriteColor' => $afterFavoriteColor,
                'customSvg' => $customSvg,
                'allowGuestInteractions' => $allowGuestInteractions,
                'headingText' => $headingText,
                'favoriteText' => $favoriteText,
                'unfavoriteText' => $unfavoriteText,
            ]);
        }

        $aggregate = Plugin::getInstance()->favoritesAggregates->getByElementFieldSite(
            (int)$element->id,
            (int)$this->id,
            (int)$element->siteId
        );

        if ($aggregate === null) {
            return new Favorite([
                'enabled' => $enabled,
                'elementId' => (int)$element->id,
                'fieldId' => (int)$this->id,
                'siteId' => (int)$element->siteId,
                'icon' => $icon,
                'emojiIcon' => $emojiIcon,
                'beforeFavoriteColor' => $beforeFavoriteColor,
                'afterFavoriteColor' => $afterFavoriteColor,
                'customSvg' => $customSvg,
                'allowGuestInteractions' => $allowGuestInteractions,
                'headingText' => $headingText,
                'favoriteText' => $favoriteText,
                'unfavoriteText' => $unfavoriteText,
            ]);
        }

        return new Favorite([
            'enabled' => $enabled,
            'id' => $aggregate->id,
            'elementId' => $aggregate->elementId,
            'fieldId' => $aggregate->fieldId,
            'siteId' => $aggregate->siteId,
            'favoriteCount' => $aggregate->favoriteCount,
            'icon' => $icon,
            'emojiIcon' => $emojiIcon,
            'beforeFavoriteColor' => $beforeFavoriteColor,
            'afterFavoriteColor' => $afterFavoriteColor,
            'customSvg' => $customSvg,
            'allowGuestInteractions' => $allowGuestInteractions,
            'headingText' => $headingText,
            'favoriteText' => $favoriteText,
            'unfavoriteText' => $unfavoriteText,
        ]);
    }

    protected function inputHtml(mixed $value, ?ElementInterface $element = null, bool $inline = false): string
    {
        $favorite = $value instanceof Favorite ? $value : $this->normalizeValue($value, $element);

        return Craft::$app->getView()->renderTemplate(
            'engagement/favorites/_fields/editor.twig',
            [
                'field' => $this,
                'favorite' => $favorite,
                'iconOptions' => $this->iconOptions(),
            ]
        );
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function iconOptions(): array
    {
        return [
            ['label' => Craft::t('engagement', 'Heart'), 'value' => Settings::ICON_HEART],
            ['label' => Craft::t('engagement', 'Star'), 'value' => Settings::ICON_STAR],
            ['label' => Craft::t('engagement', 'Emoji'), 'value' => self::ICON_EMOJI],
            ['label' => Craft::t('engagement', 'None'), 'value' => self::ICON_NONE],
            ['label' => Craft::t('engagement', 'Custom SVG'), 'value' => Settings::ICON_CUSTOM_SVG],
        ];
    }

    /**
     * @return array{enabled?: bool, icon?: string, emojiIcon?: string, beforeFavoriteColor?: string, afterFavoriteColor?: string, customSvg?: ?string, allowGuestInteractions?: bool, headingText?: ?string, favoriteText?: ?string, unfavoriteText?: ?string}
     */
    private function normalizeStoredValue(mixed $value): array
    {
        if ($value instanceof Favorite) {
            return [
                'enabled' => $value->enabled,
                'icon' => $value->icon,
                'emojiIcon' => $value->emojiIcon,
                'beforeFavoriteColor' => $value->beforeFavoriteColor,
                'afterFavoriteColor' => $value->afterFavoriteColor,
                'customSvg' => $value->customSvg,
                'allowGuestInteractions' => $value->allowGuestInteractions,
                'headingText' => $value->headingText,
                'favoriteText' => $value->favoriteText,
                'unfavoriteText' => $value->unfavoriteText,
            ];
        }

        if (is_string($value) && $value !== '') {
            try {
                /** @var mixed $decoded */
                $decoded = Json::decode($value);
                $value = $decoded;
            } catch (\Throwable) {
                return [];
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $stored = [];

        if (array_key_exists('enabled', $value)) {
            $stored['enabled'] = (bool)$value['enabled'];
        }

        if (array_key_exists('icon', $value)) {
            $icon = (string)$value['icon'];
            if (in_array($icon, [
                Settings::ICON_HEART,
                Settings::ICON_STAR,
                Settings::ICON_CUSTOM_SVG,
                self::ICON_EMOJI,
                self::ICON_NONE,
            ], true)) {
                $stored['icon'] = $icon;
            }
        }

        if (array_key_exists('emojiIcon', $value)) {
            $stored['emojiIcon'] = trim((string)$value['emojiIcon']);
        }

        if (array_key_exists('beforeFavoriteColor', $value)) {
            $stored['beforeFavoriteColor'] = trim((string)$value['beforeFavoriteColor']);
        }

        if (array_key_exists('afterFavoriteColor', $value)) {
            $stored['afterFavoriteColor'] = trim((string)$value['afterFavoriteColor']);
        }

        if (array_key_exists('customSvg', $value)) {
            $stored['customSvg'] = trim((string)$value['customSvg']);
        }

        if (array_key_exists('allowGuestInteractions', $value)) {
            $stored['allowGuestInteractions'] = (bool)$value['allowGuestInteractions'];
        }

        if (array_key_exists('headingText', $value)) {
            $stored['headingText'] = trim((string)$value['headingText']);
        }

        if (array_key_exists('favoriteText', $value)) {
            $stored['favoriteText'] = trim((string)$value['favoriteText']);
        }

        if (array_key_exists('unfavoriteText', $value)) {
            $stored['unfavoriteText'] = trim((string)$value['unfavoriteText']);
        }

        return $stored;
    }
}
