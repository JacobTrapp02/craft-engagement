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
    public bool $defaultEnabled = true;
    public bool $allowEntryOverrides = true;
    public int $scale = 0;
    public string $icon = Settings::ICON_STAR;
    public bool $allowGuestRatings = false;
    public bool $allowUserRatingChange = true;
    public ?string $customSvg = null;

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
        ]];
        $rules[] = [['defaultEnabled', 'allowEntryOverrides'], 'boolean'];
        $rules[] = [['allowGuestRatings', 'allowUserRatingChange'], 'boolean'];
        $rules[] = [['customSvg'], 'string'];
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
            'engagement/_fields/rating/settings.twig',
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
            'customSvg' => $rating->customSvg,
        ]);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $stored = $this->normalizeStoredValue($value);

        $enabled = $stored['enabled'] ?? $this->defaultEnabled;
        $scale = $this->allowEntryOverrides ? ($stored['scale'] ?? $this->scale) : $this->scale;
        $icon = $this->allowEntryOverrides ? ($stored['icon'] ?? $this->icon) : $this->icon;
        $customSvg = $this->allowEntryOverrides ? ($stored['customSvg'] ?? $this->customSvg) : $this->customSvg;

        if ($element === null || !$element->id) {
            return new Rating([
                'enabled' => $enabled,
                'average' => 0,
                'voteCount' => 0,
                'scale' => $scale,
                'icon' => $icon,
                'customSvg' => $customSvg,
            ]);
        }

        $aggregate = Plugin::getInstance()->aggregates->getByElementFieldSite(
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
                'customSvg' => $customSvg,
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
            'customSvg' => $customSvg,
        ]);
    }

    protected function inputHtml(mixed $value, ?ElementInterface $element = null, bool $inline = false): string
    {
        $rating = $value instanceof Rating ? $value : $this->normalizeValue($value, $element);

        return Craft::$app->getView()->renderTemplate(
            'engagement/_fields/rating/input.twig',
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
            ['label' => Craft::t('engagement', 'Custom SVG'), 'value' => Settings::ICON_CUSTOM_SVG],
        ];
    }

    /**
     * @return array{enabled?: bool, scale?: int, icon?: string, customSvg?: ?string}
     */
    private function normalizeStoredValue(mixed $value): array
    {
        if ($value instanceof Rating) {
            return [
                'enabled' => $value->enabled,
                'scale' => $value->scale,
                'icon' => $value->icon,
                'customSvg' => $value->customSvg,
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
            ], true)) {
                $stored['icon'] = $icon;
            }
        }

        if (array_key_exists('customSvg', $value)) {
            $stored['customSvg'] = $value['customSvg'] !== null ? (string)$value['customSvg'] : null;
        }

        return $stored;
    }
}
