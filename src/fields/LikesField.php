<?php

namespace jtdev\craftengagement\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Json;
use jtdev\craftengagement\helpers\EngagementQueryHelper;
use jtdev\craftengagement\models\Like;
use jtdev\craftengagement\models\Settings;
use jtdev\craftengagement\Plugin;
use yii\db\ExpressionInterface;
use yii\db\Schema;

/**
 * Likes/dislikes field.
 */
class LikesField extends Field
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

    public bool $defaultEnabled = true;
    public bool $allowEditorOverrides = true;
    public bool $allowOverrideIconAppearance = true;
    public bool $allowOverrideIconColors = true;
    public bool $allowOverrideWidgetEnabled = true;
    public bool $allowOverrideWidgetPreview = true;
    public bool $allowOverrideGuestInteractions = true;
    public bool $allowOverrideUserVoteChange = true;
    public bool $allowOverrideHeadingText = true;
    public bool $allowOverrideLikeText = true;
    public bool $allowOverrideDislikeText = true;
    public string $icon = Settings::ICON_THUMBS;
    /**
     * @deprecated Back-compat alias for legacy single-emoji config.
     */
    public ?string $emojiIcon = null;
    public string $likeEmojiIcon = '👍';
    public string $dislikeEmojiIcon = '👎';
    public string $beforeLikeColor = '';
    public string $afterLikeColor = '';
    public string $beforeDislikeColor = '';
    public string $afterDislikeColor = '';
    public bool $allowGuestInteractions = false;
    public bool $allowUserVoteChange = true;
    public ?string $customSvg = null;
    public ?string $headingText = null;
    public ?string $likeText = null;
    public ?string $dislikeText = null;

    public static function displayName(): string
    {
        return Craft::t('engagement', 'Likes');
    }

    public static function icon(): string
    {
        return 'thumbs-up';
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
            $this->allowOverrideLikeText = $this->allowEntryTextOverrides;
            $this->allowOverrideDislikeText = $this->allowEntryTextOverrides;
        }

        if ($this->icon === '') {
            $this->icon = Settings::ICON_THUMBS;
        }

        if ($this->emojiIcon !== null && $this->emojiIcon !== '') {
            if ($this->likeEmojiIcon === '👍') {
                $this->likeEmojiIcon = $this->emojiIcon;
            }
            if ($this->dislikeEmojiIcon === '👎') {
                $this->dislikeEmojiIcon = $this->emojiIcon;
            }
        }
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['icon'], 'required'];
        $rules[] = [['icon'], 'in', 'range' => [
            Settings::ICON_THUMBS,
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
            'allowOverrideUserVoteChange',
            'allowOverrideHeadingText',
            'allowOverrideLikeText',
            'allowOverrideDislikeText',
        ], 'boolean'];
        $rules[] = [['allowGuestInteractions', 'allowUserVoteChange'], 'boolean'];
        $rules[] = [['emojiIcon', 'likeEmojiIcon', 'dislikeEmojiIcon', 'beforeLikeColor', 'afterLikeColor', 'beforeDislikeColor', 'afterDislikeColor', 'customSvg', 'headingText', 'likeText', 'dislikeText'], 'string'];
        $rules[] = [['customSvg'], 'required', 'when' => function(): bool {
            return $this->icon === Settings::ICON_CUSTOM_SVG;
        }];

        return $rules;
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'engagement/likes/_fields/admin.twig',
            [
                'field' => $this,
                'iconOptions' => $this->iconOptions(),
            ]
        );
    }

    public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        $like = $value instanceof Like ? $value : $this->normalizeValue($value, $element);

        return Json::encode([
            'enabled' => (bool)$like->enabled,
            'icon' => (string)$like->icon,
            'emojiIcon' => (string)$like->likeEmojiIcon,
            'likeEmojiIcon' => (string)$like->likeEmojiIcon,
            'dislikeEmojiIcon' => (string)$like->dislikeEmojiIcon,
            'beforeLikeColor' => (string)$like->beforeLikeColor,
            'afterLikeColor' => (string)$like->afterLikeColor,
            'beforeDislikeColor' => (string)$like->beforeDislikeColor,
            'afterDislikeColor' => (string)$like->afterDislikeColor,
            'customSvg' => $like->customSvg,
            'allowGuestInteractions' => (bool)$like->allowGuestInteractions,
            'allowUserVoteChange' => (bool)$like->allowVoteChange,
            'headingText' => $like->headingText,
            'likeText' => $like->likeText,
            'dislikeText' => $like->dislikeText,
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
        $likeEmojiIcon = ($this->allowEditorOverrides && $this->allowOverrideIconAppearance)
            ? ($stored['likeEmojiIcon'] ?? $stored['emojiIcon'] ?? $this->likeEmojiIcon)
            : $this->likeEmojiIcon;
        $dislikeEmojiIcon = ($this->allowEditorOverrides && $this->allowOverrideIconAppearance)
            ? ($stored['dislikeEmojiIcon'] ?? $stored['emojiIcon'] ?? $this->dislikeEmojiIcon)
            : $this->dislikeEmojiIcon;
        $beforeLikeColor = ($this->allowEditorOverrides && $this->allowOverrideIconColors)
            ? ($stored['beforeLikeColor'] ?? $this->beforeLikeColor)
            : $this->beforeLikeColor;
        $afterLikeColor = ($this->allowEditorOverrides && $this->allowOverrideIconColors)
            ? ($stored['afterLikeColor'] ?? $this->afterLikeColor)
            : $this->afterLikeColor;
        $beforeDislikeColor = ($this->allowEditorOverrides && $this->allowOverrideIconColors)
            ? ($stored['beforeDislikeColor'] ?? $this->beforeDislikeColor)
            : $this->beforeDislikeColor;
        $afterDislikeColor = ($this->allowEditorOverrides && $this->allowOverrideIconColors)
            ? ($stored['afterDislikeColor'] ?? $this->afterDislikeColor)
            : $this->afterDislikeColor;
        $customSvg = ($this->allowEditorOverrides && $this->allowOverrideIconAppearance)
            ? ($stored['customSvg'] ?? $this->customSvg)
            : $this->customSvg;
        $allowGuestInteractions = ($this->allowEditorOverrides && $this->allowOverrideGuestInteractions)
            ? ($stored['allowGuestInteractions'] ?? $this->allowGuestInteractions)
            : $this->allowGuestInteractions;
        $allowUserVoteChange = ($this->allowEditorOverrides && $this->allowOverrideUserVoteChange)
            ? ($stored['allowUserVoteChange'] ?? $this->allowUserVoteChange)
            : $this->allowUserVoteChange;
        $headingText = ($this->allowEditorOverrides && $this->allowOverrideHeadingText)
            ? ($stored['headingText'] ?? $this->headingText)
            : $this->headingText;
        $likeText = ($this->allowEditorOverrides && $this->allowOverrideLikeText)
            ? ($stored['likeText'] ?? $this->likeText)
            : $this->likeText;
        $dislikeText = ($this->allowEditorOverrides && $this->allowOverrideDislikeText)
            ? ($stored['dislikeText'] ?? $this->dislikeText)
            : $this->dislikeText;

        if ($element === null || !$element->id) {
            return new Like([
                'enabled' => $enabled,
                'icon' => $icon,
                'emojiIcon' => $likeEmojiIcon,
                'likeEmojiIcon' => $likeEmojiIcon,
                'dislikeEmojiIcon' => $dislikeEmojiIcon,
                'beforeLikeColor' => $beforeLikeColor,
                'afterLikeColor' => $afterLikeColor,
                'beforeDislikeColor' => $beforeDislikeColor,
                'afterDislikeColor' => $afterDislikeColor,
                'customSvg' => $customSvg,
                'allowGuestInteractions' => $allowGuestInteractions,
                'allowVoteChange' => $allowUserVoteChange,
                'headingText' => $headingText,
                'likeText' => $likeText,
                'dislikeText' => $dislikeText,
            ]);
        }

        $aggregate = Plugin::getInstance()->likesAggregates->getByElementFieldSite(
            (int)$element->id,
            (int)$this->id,
            (int)$element->siteId
        );

        if ($aggregate === null) {
            return new Like([
                'enabled' => $enabled,
                'elementId' => (int)$element->id,
                'fieldId' => (int)$this->id,
                'siteId' => (int)$element->siteId,
                'icon' => $icon,
                'emojiIcon' => $likeEmojiIcon,
                'likeEmojiIcon' => $likeEmojiIcon,
                'dislikeEmojiIcon' => $dislikeEmojiIcon,
                'beforeLikeColor' => $beforeLikeColor,
                'afterLikeColor' => $afterLikeColor,
                'beforeDislikeColor' => $beforeDislikeColor,
                'afterDislikeColor' => $afterDislikeColor,
                'customSvg' => $customSvg,
                'allowGuestInteractions' => $allowGuestInteractions,
                'allowVoteChange' => $allowUserVoteChange,
                'headingText' => $headingText,
                'likeText' => $likeText,
                'dislikeText' => $dislikeText,
            ]);
        }

        return new Like([
            'enabled' => $enabled,
            'id' => $aggregate->id,
            'elementId' => $aggregate->elementId,
            'fieldId' => $aggregate->fieldId,
            'siteId' => $aggregate->siteId,
            'likeCount' => $aggregate->likeCount,
            'dislikeCount' => $aggregate->dislikeCount,
            'icon' => $icon,
            'emojiIcon' => $likeEmojiIcon,
            'likeEmojiIcon' => $likeEmojiIcon,
            'dislikeEmojiIcon' => $dislikeEmojiIcon,
            'beforeLikeColor' => $beforeLikeColor,
            'afterLikeColor' => $afterLikeColor,
            'beforeDislikeColor' => $beforeDislikeColor,
            'afterDislikeColor' => $afterDislikeColor,
            'customSvg' => $customSvg,
            'allowGuestInteractions' => $allowGuestInteractions,
            'allowVoteChange' => $allowUserVoteChange,
            'headingText' => $headingText,
            'likeText' => $likeText,
            'dislikeText' => $dislikeText,
        ]);
    }

    public static function queryCondition(array $instances, mixed $value, array &$params): array|string|ExpressionInterface|false|null
    {
        if (!is_array($value)) {
            return null;
        }

        /** @var self|null $field */
        $field = $instances[0] ?? null;
        if ($field === null || $field->id === null) {
            return null;
        }

        $siteId = EngagementQueryHelper::toInt($value['siteId'] ?? null);
        $conditions = ['and'];

        if ($siteId !== null) {
            $conditions[] = ['elements_sites.siteId' => $siteId];
        }

        if (array_key_exists('enabled', $value)) {
            $enabled = EngagementQueryHelper::toBool($value['enabled']);
            if ($enabled !== null) {
                $enabledCondition = self::enabledQueryCondition($instances, $params, $enabled);
                if ($enabledCondition === false) {
                    return false;
                }

                if ($enabledCondition !== null) {
                    $conditions[] = $enabledCondition;
                }
            }
        }

        $minLikes = EngagementQueryHelper::toInt($value['minLikes'] ?? null);
        $maxLikes = EngagementQueryHelper::toInt($value['maxLikes'] ?? null);
        if ($minLikes !== null && $maxLikes !== null && $minLikes > $maxLikes) {
            return false;
        }

        $minDislikes = EngagementQueryHelper::toInt($value['minDislikes'] ?? null);
        $maxDislikes = EngagementQueryHelper::toInt($value['maxDislikes'] ?? null);
        if ($minDislikes !== null && $maxDislikes !== null && $minDislikes > $maxDislikes) {
            return false;
        }

        $minTotalVotes = EngagementQueryHelper::toInt($value['minTotalVotes'] ?? null);
        $maxTotalVotes = EngagementQueryHelper::toInt($value['maxTotalVotes'] ?? null);
        $exactTotalVotes = EngagementQueryHelper::toInt($value['totalVotes'] ?? null);
        if ($exactTotalVotes !== null) {
            $minTotalVotes = $minTotalVotes ?? $exactTotalVotes;
            $maxTotalVotes = $maxTotalVotes ?? $exactTotalVotes;
        }
        if ($minTotalVotes !== null && $maxTotalVotes !== null && $minTotalVotes > $maxTotalVotes) {
            return false;
        }

        $minScore = EngagementQueryHelper::toInt($value['minScore'] ?? null);
        $maxScore = EngagementQueryHelper::toInt($value['maxScore'] ?? null);
        $exactScore = EngagementQueryHelper::toInt($value['score'] ?? null);
        if ($exactScore !== null) {
            $minScore = $minScore ?? $exactScore;
            $maxScore = $maxScore ?? $exactScore;
        }
        if ($minScore !== null && $maxScore !== null && $minScore > $maxScore) {
            return false;
        }

        $likesSql = EngagementQueryHelper::likesMetricSql((int)$field->id, 'likes', $siteId);
        $dislikesSql = EngagementQueryHelper::likesMetricSql((int)$field->id, 'dislikes', $siteId);
        $totalVotesSql = EngagementQueryHelper::likesMetricSql((int)$field->id, 'totalVotes', $siteId);
        $scoreSql = EngagementQueryHelper::likesMetricSql((int)$field->id, 'score', $siteId);

        if ($minLikes !== null) {
            $conditions[] = ['>=', new \yii\db\Expression($likesSql), $minLikes];
        }

        if ($maxLikes !== null) {
            $conditions[] = ['<=', new \yii\db\Expression($likesSql), $maxLikes];
        }

        if ($minDislikes !== null) {
            $conditions[] = ['>=', new \yii\db\Expression($dislikesSql), $minDislikes];
        }

        if ($maxDislikes !== null) {
            $conditions[] = ['<=', new \yii\db\Expression($dislikesSql), $maxDislikes];
        }

        if ($minTotalVotes !== null) {
            $conditions[] = ['>=', new \yii\db\Expression($totalVotesSql), $minTotalVotes];
        }

        if ($maxTotalVotes !== null) {
            $conditions[] = ['<=', new \yii\db\Expression($totalVotesSql), $maxTotalVotes];
        }

        if ($minScore !== null) {
            $conditions[] = ['>=', new \yii\db\Expression($scoreSql), $minScore];
        }

        if ($maxScore !== null) {
            $conditions[] = ['<=', new \yii\db\Expression($scoreSql), $maxScore];
        }

        return count($conditions) > 1 ? $conditions : null;
    }

    protected function inputHtml(mixed $value, ?ElementInterface $element = null, bool $inline = false): string
    {
        $like = $value instanceof Like ? $value : $this->normalizeValue($value, $element);

        return Craft::$app->getView()->renderTemplate(
            'engagement/likes/_fields/editor.twig',
            [
                'field' => $this,
                'like' => $like,
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
            ['label' => Craft::t('engagement', 'Thumbs'), 'value' => Settings::ICON_THUMBS],
            ['label' => Craft::t('engagement', 'Emoji'), 'value' => self::ICON_EMOJI],
            ['label' => Craft::t('engagement', 'None'), 'value' => self::ICON_NONE],
            ['label' => Craft::t('engagement', 'Custom SVG'), 'value' => Settings::ICON_CUSTOM_SVG],
        ];
    }

    /**
     * @return array{enabled?: bool, icon?: string, emojiIcon?: string, likeEmojiIcon?: string, dislikeEmojiIcon?: string, beforeLikeColor?: string, afterLikeColor?: string, beforeDislikeColor?: string, afterDislikeColor?: string, customSvg?: ?string, allowGuestInteractions?: bool, allowUserVoteChange?: bool, headingText?: ?string, likeText?: ?string, dislikeText?: ?string}
     */
    private function normalizeStoredValue(mixed $value): array
    {
        if ($value instanceof Like) {
            return [
                'enabled' => $value->enabled,
                'icon' => $value->icon,
                'emojiIcon' => $value->likeEmojiIcon,
                'likeEmojiIcon' => $value->likeEmojiIcon,
                'dislikeEmojiIcon' => $value->dislikeEmojiIcon,
                'beforeLikeColor' => $value->beforeLikeColor,
                'afterLikeColor' => $value->afterLikeColor,
                'beforeDislikeColor' => $value->beforeDislikeColor,
                'afterDislikeColor' => $value->afterDislikeColor,
                'customSvg' => $value->customSvg,
                'allowGuestInteractions' => $value->allowGuestInteractions,
                'allowUserVoteChange' => $value->allowVoteChange,
                'headingText' => $value->headingText,
                'likeText' => $value->likeText,
                'dislikeText' => $value->dislikeText,
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
                Settings::ICON_THUMBS,
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

        if (array_key_exists('likeEmojiIcon', $value)) {
            $stored['likeEmojiIcon'] = trim((string)$value['likeEmojiIcon']);
        }

        if (array_key_exists('dislikeEmojiIcon', $value)) {
            $stored['dislikeEmojiIcon'] = trim((string)$value['dislikeEmojiIcon']);
        }

        if (array_key_exists('beforeLikeColor', $value)) {
            $stored['beforeLikeColor'] = trim((string)$value['beforeLikeColor']);
        }

        if (array_key_exists('afterLikeColor', $value)) {
            $stored['afterLikeColor'] = trim((string)$value['afterLikeColor']);
        }

        if (array_key_exists('beforeDislikeColor', $value)) {
            $stored['beforeDislikeColor'] = trim((string)$value['beforeDislikeColor']);
        }

        if (array_key_exists('afterDislikeColor', $value)) {
            $stored['afterDislikeColor'] = trim((string)$value['afterDislikeColor']);
        }

        if (array_key_exists('customSvg', $value)) {
            $stored['customSvg'] = trim((string)$value['customSvg']);
        }

        if (array_key_exists('allowGuestInteractions', $value)) {
            $stored['allowGuestInteractions'] = (bool)$value['allowGuestInteractions'];
        }

        if (array_key_exists('allowUserVoteChange', $value)) {
            $stored['allowUserVoteChange'] = (bool)$value['allowUserVoteChange'];
        }

        if (array_key_exists('headingText', $value)) {
            $stored['headingText'] = trim((string)$value['headingText']);
        }

        if (array_key_exists('likeText', $value)) {
            $stored['likeText'] = trim((string)$value['likeText']);
        }

        if (array_key_exists('dislikeText', $value)) {
            $stored['dislikeText'] = trim((string)$value['dislikeText']);
        }

        return $stored;
    }

    private static function enabledQueryCondition(array $instances, array &$params, bool $enabled): array|string|false|null
    {
        /** @var self $field */
        $field = $instances[0] ?? null;
        if ($field === null) {
            return null;
        }

        $canOverride = $field->allowEditorOverrides && $field->allowOverrideWidgetEnabled;
        $defaultEnabled = $field->defaultEnabled;

        if (!$canOverride) {
            return $defaultEnabled === $enabled ? null : false;
        }

        $valueSql = self::valueSql($instances, null, $params);
        $needle = $enabled ? '"enabled":true' : '"enabled":false';
        $condition = "(($valueSql) LIKE '%$needle%')";

        if ($defaultEnabled === $enabled) {
            $condition .= " OR (($valueSql) IS NULL OR ($valueSql) = '' OR ($valueSql) NOT LIKE '%\"enabled\":%')";
        }

        return "($condition)";
    }
}
