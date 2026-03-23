<?php

namespace jtdev\craftengagement\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Json;
use jtdev\craftengagement\models\Rating;
use jtdev\craftengagement\models\Settings;
use jtdev\craftengagement\Plugin;
use yii\db\Schema;

/**
 * Ratings field.
 */
class RatingField extends Field
{
    public const ICON_EMOJI = 'emoji';

    /**
     * @deprecated Back-compat for older saved field configs.
     */
    public ?bool $allowEntryOverrides = null;

    /**
     * @deprecated Back-compat for older saved field configs.
     */
    public ?bool $allowEntryTextOverrides = null;

    public bool $defaultEnabled = true;
    public bool $allowEditorOverrides = true;
    public bool $allowOverrideScale = true;
    public bool $allowOverrideIconAppearance = true;
    public bool $allowOverrideWidgetEnabled = true;
    public bool $allowOverrideWidgetPreview = true;
    public bool $allowOverrideGuestRatings = true;
    public bool $allowOverrideUserRatingChange = true;
    public bool $allowOverrideHeadingText = true;
    public bool $allowOverrideClickToRateText = true;
    public bool $allowOverrideYourRatingText = true;
    public int $scale = 0;
    public string $icon = Settings::ICON_STAR;
    public string $emojiIcon = '⭐';
    public bool $allowGuestRatings = false;
    public bool $allowUserRatingChange = true;
    public ?string $customSvg = null;
    public ?string $headingText = null;
    public ?string $clickToRateText = 'Click to rate';
    public ?string $yourRatingText = 'Your rating: {userRating} / {scale}';

    public static function displayName(): string
    {
        return Craft::t('engagement', 'Rating');
    }

    public static function icon(): string
    {
        return 'star';
    }

    public static function dbType(): ?string
    {
        return Schema::TYPE_TEXT;
    }

    public function init(): void
    {
        parent::init();

        // Back-compat mapping for legacy field settings keys.
        if ($this->allowEntryOverrides !== null) {
            $this->allowEditorOverrides = $this->allowEntryOverrides;
        }

        if ($this->allowEntryTextOverrides !== null) {
            $this->allowOverrideHeadingText = $this->allowEntryTextOverrides;
            $this->allowOverrideClickToRateText = $this->allowEntryTextOverrides;
            $this->allowOverrideYourRatingText = $this->allowEntryTextOverrides;
        }

        if ($this->scale <= 0) {
            $this->scale = $this->pluginSettings()->defaultScale;
        }

        if ($this->icon === '') {
            $this->icon = $this->pluginSettings()->defaultIcon;
        }
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $maxScale = $this->pluginSettings()->maxScale;

        $rules[] = [['scale'], 'required'];
        $rules[] = [['scale'], 'integer', 'min' => 1, 'max' => 100];
        $rules[] = [['scale'], 'integer', 'max' => $maxScale];
        $rules[] = [['icon'], 'required'];
        $rules[] = [['icon'], 'in', 'range' => [
            Settings::ICON_STAR,
            Settings::ICON_HEART,
            Settings::ICON_THUMBS,
            Settings::ICON_CUSTOM_SVG,
            self::ICON_EMOJI,
        ]];
        $rules[] = [[
            'defaultEnabled',
            'allowEntryOverrides',
            'allowEntryTextOverrides',
            'allowEditorOverrides',
            'allowOverrideScale',
            'allowOverrideIconAppearance',
            'allowOverrideWidgetEnabled',
            'allowOverrideWidgetPreview',
            'allowOverrideGuestRatings',
            'allowOverrideUserRatingChange',
            'allowOverrideHeadingText',
            'allowOverrideClickToRateText',
            'allowOverrideYourRatingText',
        ], 'boolean'];
        $rules[] = [['allowGuestRatings', 'allowUserRatingChange'], 'boolean'];
        $rules[] = [['emojiIcon', 'customSvg', 'headingText', 'clickToRateText', 'yourRatingText'], 'string'];
        $rules[] = [['customSvg'], 'required', 'when' => function(): bool {
            return $this->icon === Settings::ICON_CUSTOM_SVG;
        }];

        return $rules;
    }

    public function beforeSave(bool $isNew): bool
    {
        $settings = $this->pluginSettings();

        // Keep existing fields valid if plugin maxScale is reduced.
        if ($this->scale > $settings->maxScale) {
            $this->scale = $settings->maxScale;
        }

        return parent::beforeSave($isNew);
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'engagement/ratings/_fields/admin.twig',
            [
                'field' => $this,
                'pluginSettings' => $this->pluginSettings(),
                'iconOptions' => $this->iconOptions(),
            ]
        );
    }

    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $rating = $value instanceof Rating ? $value : $this->normalizeValue($value, $element);

        return Json::encode([
            'enabled' => (bool)$rating->enabled,
            'scale' => (int)$rating->scale,
            'icon' => (string)$rating->icon,
            'emojiIcon' => (string)$rating->emojiIcon,
            'customSvg' => $rating->customSvg,
            'allowGuestRatings' => (bool)$rating->allowGuestRatings,
            'allowUserRatingChange' => (bool)$rating->allowUserRatingChange,
            'headingText' => $rating->headingText,
            'clickToRateText' => $rating->clickToRateText,
            'yourRatingText' => $rating->yourRatingText,
        ]);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $stored = $this->normalizeStoredValue($value);

        $enabled = ($this->allowEditorOverrides && $this->allowOverrideWidgetEnabled)
            ? ($stored['enabled'] ?? $this->defaultEnabled)
            : $this->defaultEnabled;
        $scale = ($this->allowEditorOverrides && $this->allowOverrideScale)
            ? ($stored['scale'] ?? $this->scale)
            : $this->scale;
        $icon = ($this->allowEditorOverrides && $this->allowOverrideIconAppearance)
            ? ($stored['icon'] ?? $this->icon)
            : $this->icon;
        $emojiIcon = ($this->allowEditorOverrides && $this->allowOverrideIconAppearance)
            ? ($stored['emojiIcon'] ?? $this->emojiIcon)
            : $this->emojiIcon;
        $customSvg = ($this->allowEditorOverrides && $this->allowOverrideIconAppearance)
            ? ($stored['customSvg'] ?? $this->customSvg)
            : $this->customSvg;
        $allowGuestRatings = ($this->allowEditorOverrides && $this->allowOverrideGuestRatings)
            ? ($stored['allowGuestRatings'] ?? $this->allowGuestRatings)
            : $this->allowGuestRatings;
        $allowUserRatingChange = ($this->allowEditorOverrides && $this->allowOverrideUserRatingChange)
            ? ($stored['allowUserRatingChange'] ?? $this->allowUserRatingChange)
            : $this->allowUserRatingChange;
        $headingText = ($this->allowEditorOverrides && $this->allowOverrideHeadingText)
            ? ($stored['headingText'] ?? $this->headingText)
            : $this->headingText;
        $clickToRateText = ($this->allowEditorOverrides && $this->allowOverrideClickToRateText)
            ? ($stored['clickToRateText'] ?? $this->clickToRateText)
            : $this->clickToRateText;
        $yourRatingText = ($this->allowEditorOverrides && $this->allowOverrideYourRatingText)
            ? ($stored['yourRatingText'] ?? $this->yourRatingText)
            : $this->yourRatingText;

        if ($element === null || !$element->id) {
            return new Rating([
                'enabled' => $enabled,
                'average' => 0,
                'voteCount' => 0,
                'scale' => $scale,
                'icon' => $icon,
                'emojiIcon' => $emojiIcon,
                'customSvg' => $customSvg,
                'allowGuestRatings' => $allowGuestRatings,
                'allowUserRatingChange' => $allowUserRatingChange,
                'headingText' => $headingText,
                'clickToRateText' => $clickToRateText,
                'yourRatingText' => $yourRatingText,
            ]);
        }

        $aggregate = Plugin::getInstance()->ratingAggregates->getByElementFieldSite(
            (int)$element->id,
            (int)$this->id,
            (int)$element->siteId
        );

        if ($aggregate === null) {
            return new Rating([
                'enabled' => $enabled,
                'elementId' => (int)$element->id,
                'fieldId' => (int)$this->id,
                'siteId' => (int)$element->siteId,
                'average' => 0,
                'voteCount' => 0,
                'scale' => $scale,
                'icon' => $icon,
                'emojiIcon' => $emojiIcon,
                'customSvg' => $customSvg,
                'allowGuestRatings' => $allowGuestRatings,
                'allowUserRatingChange' => $allowUserRatingChange,
                'headingText' => $headingText,
                'clickToRateText' => $clickToRateText,
                'yourRatingText' => $yourRatingText,
            ]);
        }

        return new Rating([
            'enabled' => $enabled,
            'id' => $aggregate->id,
            'elementId' => $aggregate->elementId,
            'fieldId' => $aggregate->fieldId,
            'siteId' => $aggregate->siteId,
            'average' => $aggregate->average,
            'voteCount' => $aggregate->voteCount,
            'scale' => $scale,
            'icon' => $icon,
            'emojiIcon' => $emojiIcon,
            'customSvg' => $customSvg,
            'allowGuestRatings' => $allowGuestRatings,
            'allowUserRatingChange' => $allowUserRatingChange,
            'headingText' => $headingText,
            'clickToRateText' => $clickToRateText,
            'yourRatingText' => $yourRatingText,
        ]);
    }

    protected function inputHtml(mixed $value, ?ElementInterface $element = null, bool $inline = false): string
    {
        $rating = $value instanceof Rating ? $value : $this->normalizeValue($value, $element);

        return Craft::$app->getView()->renderTemplate(
            'engagement/ratings/_fields/editor.twig',
            [
                'field' => $this,
                'rating' => $rating,
                'iconOptions' => $this->iconOptions(),
                'maxScale' => $this->pluginSettings()->maxScale,
            ]
        );
    }

    private function pluginSettings(): Settings
    {
        /** @var Settings $settings */
        $settings = Plugin::getInstance()->getSettings();

        return $settings;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function iconOptions(): array
    {
        return [
            ['label' => Craft::t('engagement', 'Star'), 'value' => Settings::ICON_STAR],
            ['label' => Craft::t('engagement', 'Heart'), 'value' => Settings::ICON_HEART],
            ['label' => Craft::t('engagement', 'Thumbs'), 'value' => Settings::ICON_THUMBS],
            ['label' => Craft::t('engagement', 'Emoji'), 'value' => self::ICON_EMOJI],
            ['label' => Craft::t('engagement', 'Custom SVG'), 'value' => Settings::ICON_CUSTOM_SVG],
        ];
    }

    /**
     * @return array{enabled?: bool, scale?: int, icon?: string, emojiIcon?: string, customSvg?: ?string, allowGuestRatings?: bool, allowUserRatingChange?: bool, headingText?: ?string, clickToRateText?: ?string, yourRatingText?: ?string}
     */
    private function normalizeStoredValue(mixed $value): array
    {
        if ($value instanceof Rating) {
            return [
                'enabled' => $value->enabled,
                'scale' => $value->scale,
                'icon' => $value->icon,
                'emojiIcon' => $value->emojiIcon,
                'customSvg' => $value->customSvg,
                'allowGuestRatings' => $value->allowGuestRatings,
                'allowUserRatingChange' => $value->allowUserRatingChange,
                'headingText' => $value->headingText,
                'clickToRateText' => $value->clickToRateText,
                'yourRatingText' => $value->yourRatingText,
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

        if (array_key_exists('scale', $value)) {
            $stored['scale'] = max(1, min((int)$value['scale'], $this->pluginSettings()->maxScale));
        }

        if (array_key_exists('icon', $value)) {
            $icon = (string)$value['icon'];
            if (in_array($icon, [
                Settings::ICON_STAR,
                Settings::ICON_HEART,
                Settings::ICON_THUMBS,
                Settings::ICON_CUSTOM_SVG,
                self::ICON_EMOJI,
            ], true)) {
                $stored['icon'] = $icon;
            }
        }

        if (array_key_exists('emojiIcon', $value)) {
            $stored['emojiIcon'] = trim((string)$value['emojiIcon']) ?: '⭐';
        }

        if (array_key_exists('customSvg', $value)) {
            $stored['customSvg'] = $value['customSvg'] !== null ? (string)$value['customSvg'] : null;
        }

        if (array_key_exists('allowGuestRatings', $value)) {
            $stored['allowGuestRatings'] = (bool)$value['allowGuestRatings'];
        }

        if (array_key_exists('allowUserRatingChange', $value)) {
            $stored['allowUserRatingChange'] = (bool)$value['allowUserRatingChange'];
        }

        if (array_key_exists('headingText', $value)) {
            $stored['headingText'] = $value['headingText'] !== null ? trim((string)$value['headingText']) : null;
        }

        if (array_key_exists('clickToRateText', $value)) {
            $stored['clickToRateText'] = $value['clickToRateText'] !== null ? trim((string)$value['clickToRateText']) : null;
        }

        if (array_key_exists('yourRatingText', $value)) {
            $stored['yourRatingText'] = $value['yourRatingText'] !== null ? trim((string)$value['yourRatingText']) : null;
        }

        return $stored;
    }

}
