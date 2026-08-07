<?php

namespace jtdev\craftengagement\services;

use Craft;
use craft\base\Component;
use craft\helpers\UrlHelper;
use craft\web\View;
use jtdev\craftengagement\models\Favorite;
use jtdev\craftengagement\models\Like;
use jtdev\craftengagement\models\Rating;
use jtdev\craftengagement\models\Settings;
use jtdev\craftengagement\Plugin;
use jtdev\craftengagement\web\assets\DeferredWidgetAsset;
use Twig\Markup;
use yii\helpers\HtmlPurifier;

/**
 * Twig rendering helpers exposed via craft.engagement.*
 */
class TwigService extends Component
{
    /**
     * Render a widget by inferring field value type (rating/likes/favorites).
     */
    public function render(mixed $fieldValue, ?array $options = null): Markup|string
    {
        if ($fieldValue instanceof Rating) {
            return $this->renderRating($fieldValue, $options);
        }

        if ($fieldValue instanceof Like) {
            return $this->renderLikes($fieldValue, $options);
        }

        if ($fieldValue instanceof Favorite) {
            return $this->renderFavorite($fieldValue, $options);
        }

        if (is_array($fieldValue)) {
            if (array_key_exists('scale', $fieldValue) || array_key_exists('voteCount', $fieldValue)) {
                return $this->renderRating($fieldValue, $options);
            }

            if (array_key_exists('likeCount', $fieldValue) || array_key_exists('dislikeCount', $fieldValue)) {
                return $this->renderLikes($fieldValue, $options);
            }

            if (array_key_exists('favoriteCount', $fieldValue) || array_key_exists('isFavorited', $fieldValue)) {
                return $this->renderFavorite($fieldValue, $options);
            }
        }

        return '';
    }

    public function renderRating(mixed $fieldValue, ?array $options = null): Markup|string
    {
        $data = $this->normalizeRatingFieldData($fieldValue);
        if ($data === null || ($data['enabled'] ?? true) === false) {
            return '';
        }

        $fieldName = $this->resolveFieldName($data['fieldId'] ?? null);
        $widgetMode = $this->resolveWidgetMode($options);
        $isShell = $widgetMode === 'shell';
        $currentUser = $isShell ? null : Craft::$app->getUser()->getIdentity();
        $isLoggedIn = !$isShell && $currentUser !== null;
        $userRating = null;

        if (!$isShell && !empty($data['id'])) {
            if ($isLoggedIn) {
                $vote = Plugin::getInstance()->ratingVotes->getByAggregateAndUserId((int)$data['id'], (int)$currentUser->id);
                $userRating = $vote?->rating;
            } elseif (($data['allowGuestRatings'] ?? false) === true) {
                $session = Craft::$app->getSession();
                $session->open();
                $vote = Plugin::getInstance()->ratingVotes->getByAggregateAndSessionId((int)$data['id'], $session->getId());
                $userRating = $vote?->rating;
            }
        }

        $context = $this->baseWidgetContext(
            $data,
            'rating',
            'engagement/ratings/cast-rating',
            $isLoggedIn,
            (bool)($data['allowGuestRatings'] ?? false),
        );
        $context['widgetConfig'] = array_merge($context['widgetConfig'], [
            'scale' => $data['scale'] ?? 5,
            'userRating' => $userRating,
            'average' => $data['average'] ?? 0,
            'voteCount' => $data['voteCount'] ?? 0,
            'headingText' => $data['headingText'] ?? '',
            'clickToRateText' => $data['clickToRateText'] ?? '',
            'yourRatingText' => $data['yourRatingText'] ?? '',
            'fieldName' => $fieldName ?? Craft::t('engagement', 'Rating'),
            'updatedText' => Craft::t('engagement', 'Rating updated.'),
            'errorText' => Craft::t('engagement', 'Could not submit rating right now.'),
        ]);

        $html = $this->renderPluginTemplate('engagement/ratings/_render/widget.twig', array_merge($context, [
            'widgetMode' => $widgetMode,
            'widgetUid' => $widgetMode === 'deferred' ? $this->widgetUid('rating', $data) : null,
            'previewMode' => $isShell,
            'label' => $fieldName ?? Craft::t('engagement', 'Rating'),
            'icon' => $data['icon'] ?? 'star',
            'emojiIcon' => $data['emojiIcon'] ?? '⭐',
            'customSvg' => $data['customSvg'] ?? null,
            'scale' => $data['scale'] ?? 5,
            'average' => $data['average'] ?? 0,
            'roundedAverage' => $data['roundedAverage'] ?? 0,
            'voteCount' => $data['voteCount'] ?? 0,
            'headingText' => $data['headingText'] ?? null,
            'clickToRateText' => $data['clickToRateText'] ?? null,
            'yourRatingText' => $data['yourRatingText'] ?? null,
            'userRating' => $userRating,
            'widgetTemplates' => $this->resolveWidgetTemplateOverrides($options),
        ]));

        return new Markup($html, Craft::$app->charset ?: 'UTF-8');
    }

    public function renderLikes(mixed $fieldValue, ?array $options = null): Markup|string
    {
        $data = $this->normalizeLikesFieldData($fieldValue);
        if ($data === null || ($data['enabled'] ?? true) === false) {
            return '';
        }

        $fieldName = $this->resolveFieldName($data['fieldId'] ?? null);
        $currentUser = Craft::$app->getUser()->getIdentity();
        $isLoggedIn = $currentUser !== null;
        $userVote = null;

        if (!empty($data['id'])) {
            if ($isLoggedIn) {
                $vote = Plugin::getInstance()->likesVotes->getByAggregateAndUserId((int)$data['id'], (int)$currentUser->id);
                $userVote = $vote?->value;
            } elseif (($data['allowGuestInteractions'] ?? false) === true) {
                $session = Craft::$app->getSession();
                $session->open();
                $vote = Plugin::getInstance()->likesVotes->getByAggregateAndSessionId((int)$data['id'], $session->getId());
                $userVote = $vote?->value;
            }
        }

        $widgetMode = $this->resolveWidgetMode($options);
        $context = $this->baseWidgetContext(
            $data,
            'likes',
            'engagement/likes/cast-like',
            $isLoggedIn,
            (bool)($data['allowGuestInteractions'] ?? false),
        );
        $context['widgetConfig'] = array_merge($context['widgetConfig'], [
            'userVote' => $userVote,
            'likeCount' => $data['likeCount'] ?? 0,
            'dislikeCount' => $data['dislikeCount'] ?? 0,
            'headingText' => $data['headingText'] ?? '',
            'likeText' => $data['likeText'] ?? '',
            'dislikeText' => $data['dislikeText'] ?? '',
            'beforeLikeColor' => $data['beforeLikeColor'] ?? '',
            'afterLikeColor' => $data['afterLikeColor'] ?? '',
            'beforeDislikeColor' => $data['beforeDislikeColor'] ?? '',
            'afterDislikeColor' => $data['afterDislikeColor'] ?? '',
            'fieldName' => $fieldName ?? Craft::t('engagement', 'Likes'),
            'updatedText' => Craft::t('engagement', 'Vote updated.'),
            'errorText' => Craft::t('engagement', 'Could not update vote right now.'),
        ]);

        $html = $this->renderPluginTemplate('engagement/likes/_render/widget.twig', array_merge($context, [
            'widgetMode' => $widgetMode,
            'widgetUid' => $widgetMode === 'deferred' ? $this->widgetUid('likes', $data) : null,
            'label' => $fieldName ?? Craft::t('engagement', 'Likes'),
            'icon' => $data['icon'] ?? 'thumbs',
            'emojiIcon' => $data['emojiIcon'] ?? $data['likeEmojiIcon'] ?? '👍',
            'likeEmojiIcon' => $data['likeEmojiIcon'] ?? $data['emojiIcon'] ?? '👍',
            'dislikeEmojiIcon' => $data['dislikeEmojiIcon'] ?? '👎',
            'beforeLikeColor' => $data['beforeLikeColor'] ?? '',
            'afterLikeColor' => $data['afterLikeColor'] ?? '',
            'beforeDislikeColor' => $data['beforeDislikeColor'] ?? '',
            'afterDislikeColor' => $data['afterDislikeColor'] ?? '',
            'likeCustomSvg' => $data['likeCustomSvg'] ?? null,
            'dislikeCustomSvg' => $data['dislikeCustomSvg'] ?? null,
            'likeCount' => $data['likeCount'] ?? 0,
            'dislikeCount' => $data['dislikeCount'] ?? 0,
            'headingText' => $data['headingText'] ?? null,
            'likeText' => $data['likeText'] ?? null,
            'dislikeText' => $data['dislikeText'] ?? null,
            'userVote' => $userVote,
            'widgetTemplates' => $this->resolveWidgetTemplateOverrides($options),
        ]));

        return new Markup($html, Craft::$app->charset ?: 'UTF-8');
    }

    public function renderFavorite(mixed $fieldValue, ?array $options = null): Markup|string
    {
        $data = $this->normalizeFavoritesFieldData($fieldValue);
        if ($data === null) {
            // Back-compat for older templates that used renderFavorite() for ratings.
            return $this->renderRating($fieldValue, $options);
        }

        if (($data['enabled'] ?? true) === false) {
            return '';
        }

        $fieldName = $this->resolveFieldName($data['fieldId'] ?? null);
        $currentUser = Craft::$app->getUser()->getIdentity();
        $isLoggedIn = $currentUser !== null;
        $isFavorited = (bool)($data['isFavorited'] ?? false);

        if (!empty($data['id'])) {
            if ($isLoggedIn) {
                $entry = Plugin::getInstance()->favoritesEntries->getByAggregateAndUserId((int)$data['id'], (int)$currentUser->id);
                $isFavorited = $entry !== null;
            } elseif (($data['allowGuestInteractions'] ?? false) === true) {
                $session = Craft::$app->getSession();
                $session->open();
                $entry = Plugin::getInstance()->favoritesEntries->getByAggregateAndSessionId((int)$data['id'], $session->getId());
                $isFavorited = $entry !== null;
            }
        }

        $widgetMode = $this->resolveWidgetMode($options);
        $context = $this->baseWidgetContext(
            $data,
            'favorite',
            'engagement/favorites/toggle-favorite',
            $isLoggedIn,
            (bool)($data['allowGuestInteractions'] ?? false),
        );
        $context['widgetConfig'] = array_merge($context['widgetConfig'], [
            'isFavorited' => $isFavorited,
            'favoriteCount' => $data['favoriteCount'] ?? 0,
            'headingText' => $data['headingText'] ?? '',
            'favoriteText' => $data['favoriteText'] ?? '',
            'unfavoriteText' => $data['unfavoriteText'] ?? '',
            'beforeFavoriteColor' => $data['beforeFavoriteColor'] ?? '',
            'afterFavoriteColor' => $data['afterFavoriteColor'] ?? '',
            'fieldName' => $fieldName ?? Craft::t('engagement', 'Favorite'),
            'updatedText' => Craft::t('engagement', 'Favorite updated.'),
            'errorText' => Craft::t('engagement', 'Could not update favorite right now.'),
        ]);

        $html = $this->renderPluginTemplate('engagement/favorites/_render/widget.twig', array_merge($context, [
            'widgetMode' => $widgetMode,
            'widgetUid' => $widgetMode === 'deferred' ? $this->widgetUid('favorite', $data) : null,
            'label' => $fieldName ?? Craft::t('engagement', 'Favorite'),
            'icon' => $data['icon'] ?? 'heart',
            'emojiIcon' => $data['emojiIcon'] ?? '⭐',
            'beforeFavoriteColor' => $data['beforeFavoriteColor'] ?? '',
            'afterFavoriteColor' => $data['afterFavoriteColor'] ?? '',
            'customSvg' => $data['customSvg'] ?? null,
            'favoriteCount' => $data['favoriteCount'] ?? 0,
            'headingText' => $data['headingText'] ?? null,
            'favoriteText' => $data['favoriteText'] ?? null,
            'unfavoriteText' => $data['unfavoriteText'] ?? null,
            'isFavorited' => $isFavorited,
            'widgetTemplates' => $this->resolveWidgetTemplateOverrides($options),
        ]));

        return new Markup($html, Craft::$app->charset ?: 'UTF-8');
    }

    /**
     * @return array{id?: ?int, elementId?: ?int, siteId?: ?int, fieldId?: ?int, enabled?: bool, scale?: int, icon?: string, emojiIcon?: string, customSvg?: ?string, average?: float, roundedAverage?: float, voteCount?: int, headingText?: ?string, clickToRateText?: ?string, yourRatingText?: ?string, allowGuestRatings?: bool}|null
     */
    private function normalizeRatingFieldData(mixed $fieldValue): ?array
    {
        if ($fieldValue instanceof Rating) {
            return [
                'id' => $fieldValue->id,
                'elementId' => $fieldValue->elementId,
                'siteId' => $fieldValue->siteId,
                'fieldId' => $fieldValue->fieldId,
                'enabled' => $fieldValue->enabled,
                'scale' => $fieldValue->scale,
                'icon' => $fieldValue->icon,
                'emojiIcon' => $fieldValue->emojiIcon,
                'customSvg' => $fieldValue->customSvg,
                'average' => $fieldValue->average,
                'roundedAverage' => $fieldValue->roundedAverage,
                'voteCount' => $fieldValue->voteCount,
                'headingText' => $fieldValue->headingText,
                'clickToRateText' => $fieldValue->clickToRateText,
                'yourRatingText' => $fieldValue->yourRatingText,
                'allowGuestRatings' => $fieldValue->allowGuestRatings,
            ];
        }

        if (is_array($fieldValue)) {
            return [
                'id' => isset($fieldValue['id']) ? (int)$fieldValue['id'] : null,
                'elementId' => isset($fieldValue['elementId']) ? (int)$fieldValue['elementId'] : null,
                'siteId' => isset($fieldValue['siteId']) ? (int)$fieldValue['siteId'] : null,
                'fieldId' => isset($fieldValue['fieldId']) ? (int)$fieldValue['fieldId'] : null,
                'enabled' => isset($fieldValue['enabled']) ? (bool)$fieldValue['enabled'] : true,
                'scale' => isset($fieldValue['scale']) ? (int)$fieldValue['scale'] : 5,
                'icon' => isset($fieldValue['icon']) ? (string)$fieldValue['icon'] : 'star',
                'emojiIcon' => isset($fieldValue['emojiIcon']) ? (string)$fieldValue['emojiIcon'] : '⭐',
                'customSvg' => $fieldValue['customSvg'] ?? null,
                'average' => isset($fieldValue['average']) ? (float)$fieldValue['average'] : 0,
                'roundedAverage' => isset($fieldValue['roundedAverage']) ? (float)$fieldValue['roundedAverage'] : 0,
                'voteCount' => isset($fieldValue['voteCount']) ? (int)$fieldValue['voteCount'] : 0,
                'headingText' => isset($fieldValue['headingText']) ? trim((string)$fieldValue['headingText']) : null,
                'clickToRateText' => isset($fieldValue['clickToRateText']) ? trim((string)$fieldValue['clickToRateText']) : null,
                'yourRatingText' => isset($fieldValue['yourRatingText']) ? trim((string)$fieldValue['yourRatingText']) : null,
                'allowGuestRatings' => isset($fieldValue['allowGuestRatings']) ? (bool)$fieldValue['allowGuestRatings'] : false,
            ];
        }

        return null;
    }

    /**
     * @return array{id?: ?int, elementId?: ?int, siteId?: ?int, fieldId?: ?int, enabled?: bool, icon?: string, emojiIcon?: string, likeEmojiIcon?: string, dislikeEmojiIcon?: string, beforeLikeColor?: string, afterLikeColor?: string, beforeDislikeColor?: string, afterDislikeColor?: string, likeCustomSvg?: ?string, dislikeCustomSvg?: ?string, likeCount?: int, dislikeCount?: int, headingText?: ?string, likeText?: ?string, dislikeText?: ?string, allowGuestInteractions?: bool, allowVoteChange?: bool}|null
     */
    private function normalizeLikesFieldData(mixed $fieldValue): ?array
    {
        if ($fieldValue instanceof Like) {
            return [
                'id' => $fieldValue->id,
                'elementId' => $fieldValue->elementId,
                'siteId' => $fieldValue->siteId,
                'fieldId' => $fieldValue->fieldId,
                'enabled' => $fieldValue->enabled,
                'icon' => $fieldValue->icon,
                'emojiIcon' => $fieldValue->likeEmojiIcon,
                'likeEmojiIcon' => $fieldValue->likeEmojiIcon,
                'dislikeEmojiIcon' => $fieldValue->dislikeEmojiIcon,
                'beforeLikeColor' => $fieldValue->beforeLikeColor,
                'afterLikeColor' => $fieldValue->afterLikeColor,
                'beforeDislikeColor' => $fieldValue->beforeDislikeColor,
                'afterDislikeColor' => $fieldValue->afterDislikeColor,
                'likeCustomSvg' => $fieldValue->likeCustomSvg,
                'dislikeCustomSvg' => $fieldValue->dislikeCustomSvg,
                'likeCount' => $fieldValue->likeCount,
                'dislikeCount' => $fieldValue->dislikeCount,
                'headingText' => $fieldValue->headingText,
                'likeText' => $fieldValue->likeText,
                'dislikeText' => $fieldValue->dislikeText,
                'allowGuestInteractions' => $fieldValue->allowGuestInteractions,
                'allowVoteChange' => $fieldValue->allowVoteChange,
            ];
        }

        if (is_array($fieldValue)) {
            return [
                'id' => isset($fieldValue['id']) ? (int)$fieldValue['id'] : null,
                'elementId' => isset($fieldValue['elementId']) ? (int)$fieldValue['elementId'] : null,
                'siteId' => isset($fieldValue['siteId']) ? (int)$fieldValue['siteId'] : null,
                'fieldId' => isset($fieldValue['fieldId']) ? (int)$fieldValue['fieldId'] : null,
                'enabled' => isset($fieldValue['enabled']) ? (bool)$fieldValue['enabled'] : true,
                'icon' => isset($fieldValue['icon']) ? (string)$fieldValue['icon'] : 'thumbs',
                'emojiIcon' => isset($fieldValue['emojiIcon']) ? (string)$fieldValue['emojiIcon'] : '👍',
                'likeEmojiIcon' => isset($fieldValue['likeEmojiIcon']) ? (string)$fieldValue['likeEmojiIcon'] : (isset($fieldValue['emojiIcon']) ? (string)$fieldValue['emojiIcon'] : '👍'),
                'dislikeEmojiIcon' => isset($fieldValue['dislikeEmojiIcon']) ? (string)$fieldValue['dislikeEmojiIcon'] : '👎',
                'beforeLikeColor' => isset($fieldValue['beforeLikeColor']) ? (string)$fieldValue['beforeLikeColor'] : '',
                'afterLikeColor' => isset($fieldValue['afterLikeColor']) ? (string)$fieldValue['afterLikeColor'] : '',
                'beforeDislikeColor' => isset($fieldValue['beforeDislikeColor']) ? (string)$fieldValue['beforeDislikeColor'] : '',
                'afterDislikeColor' => isset($fieldValue['afterDislikeColor']) ? (string)$fieldValue['afterDislikeColor'] : '',
                'likeCustomSvg' => $fieldValue['likeCustomSvg'] ?? null,
                'dislikeCustomSvg' => $fieldValue['dislikeCustomSvg'] ?? null,
                'likeCount' => isset($fieldValue['likeCount']) ? (int)$fieldValue['likeCount'] : 0,
                'dislikeCount' => isset($fieldValue['dislikeCount']) ? (int)$fieldValue['dislikeCount'] : 0,
                'headingText' => isset($fieldValue['headingText']) ? trim((string)$fieldValue['headingText']) : null,
                'likeText' => isset($fieldValue['likeText']) ? trim((string)$fieldValue['likeText']) : null,
                'dislikeText' => isset($fieldValue['dislikeText']) ? trim((string)$fieldValue['dislikeText']) : null,
                'allowGuestInteractions' => isset($fieldValue['allowGuestInteractions']) ? (bool)$fieldValue['allowGuestInteractions'] : false,
                'allowVoteChange' => isset($fieldValue['allowVoteChange']) ? (bool)$fieldValue['allowVoteChange'] : true,
            ];
        }

        return null;
    }

    /**
     * @return array{id?: ?int, elementId?: ?int, siteId?: ?int, fieldId?: ?int, enabled?: bool, icon?: string, emojiIcon?: string, beforeFavoriteColor?: string, afterFavoriteColor?: string, customSvg?: ?string, favoriteCount?: int, headingText?: ?string, favoriteText?: ?string, unfavoriteText?: ?string, isFavorited?: bool, allowGuestInteractions?: bool}|null
     */
    private function normalizeFavoritesFieldData(mixed $fieldValue): ?array
    {
        if ($fieldValue instanceof Favorite) {
            return [
                'id' => $fieldValue->id,
                'elementId' => $fieldValue->elementId,
                'siteId' => $fieldValue->siteId,
                'fieldId' => $fieldValue->fieldId,
                'enabled' => $fieldValue->enabled,
                'icon' => $fieldValue->icon,
                'emojiIcon' => $fieldValue->emojiIcon,
                'beforeFavoriteColor' => $fieldValue->beforeFavoriteColor,
                'afterFavoriteColor' => $fieldValue->afterFavoriteColor,
                'customSvg' => $fieldValue->customSvg,
                'favoriteCount' => $fieldValue->favoriteCount,
                'headingText' => $fieldValue->headingText,
                'favoriteText' => $fieldValue->favoriteText,
                'unfavoriteText' => $fieldValue->unfavoriteText,
                'isFavorited' => $fieldValue->isFavorited,
                'allowGuestInteractions' => $fieldValue->allowGuestInteractions,
            ];
        }

        if (is_array($fieldValue)) {
            return [
                'id' => isset($fieldValue['id']) ? (int)$fieldValue['id'] : null,
                'elementId' => isset($fieldValue['elementId']) ? (int)$fieldValue['elementId'] : null,
                'siteId' => isset($fieldValue['siteId']) ? (int)$fieldValue['siteId'] : null,
                'fieldId' => isset($fieldValue['fieldId']) ? (int)$fieldValue['fieldId'] : null,
                'enabled' => isset($fieldValue['enabled']) ? (bool)$fieldValue['enabled'] : true,
                'icon' => isset($fieldValue['icon']) ? (string)$fieldValue['icon'] : 'heart',
                'emojiIcon' => isset($fieldValue['emojiIcon']) ? (string)$fieldValue['emojiIcon'] : '⭐',
                'beforeFavoriteColor' => isset($fieldValue['beforeFavoriteColor']) ? (string)$fieldValue['beforeFavoriteColor'] : '',
                'afterFavoriteColor' => isset($fieldValue['afterFavoriteColor']) ? (string)$fieldValue['afterFavoriteColor'] : '',
                'customSvg' => $fieldValue['customSvg'] ?? null,
                'favoriteCount' => isset($fieldValue['favoriteCount']) ? (int)$fieldValue['favoriteCount'] : 0,
                'headingText' => isset($fieldValue['headingText']) ? trim((string)$fieldValue['headingText']) : null,
                'favoriteText' => isset($fieldValue['favoriteText']) ? trim((string)$fieldValue['favoriteText']) : null,
                'unfavoriteText' => isset($fieldValue['unfavoriteText']) ? trim((string)$fieldValue['unfavoriteText']) : null,
                'isFavorited' => isset($fieldValue['isFavorited']) ? (bool)$fieldValue['isFavorited'] : false,
                'allowGuestInteractions' => isset($fieldValue['allowGuestInteractions']) ? (bool)$fieldValue['allowGuestInteractions'] : false,
            ];
        }

        return null;
    }

    private function resolveFieldName(?int $fieldId): ?string
    {
        if ($fieldId === null) {
            return null;
        }

        $field = Craft::$app->getFields()->getFieldById($fieldId);

        return $field?->name;
    }

    private function resolveLoginUrl(): string
    {
        $settings = $this->settings();
        $url = $this->normalizeUrl($settings->loginUrl) ?: UrlHelper::url('login');
        $redirectParam = trim($settings->loginRedirectParam);

        if ($redirectParam !== '') {
            $redirectTo = Craft::$app->getRequest()->getAbsoluteUrl();
            $url = UrlHelper::urlWithParams($url, [
                $redirectParam => $redirectTo,
            ]);
        }

        return $url;
    }

    private function resolveGuestInteractionMode(): string
    {
        $mode = trim($this->settings()->guestInteractionMode);

        if (in_array($mode, [
            Settings::GUEST_INTERACTION_MODE_MESSAGE,
            Settings::GUEST_INTERACTION_MODE_REDIRECT,
        ], true)) {
            return $mode;
        }

        return Settings::GUEST_INTERACTION_MODE_MESSAGE;
    }

    private function resolveGuestInteractionMessage(): string
    {
        $message = trim($this->settings()->guestInteractionMessage);

        return $message !== ''
            ? $message
            : Craft::t('engagement', 'Please log in or register to interact.');
    }

    private function resolveGuestInteractionMessageHtml(): string
    {
        $message = $this->resolveGuestInteractionMessage();
        $sanitized = HtmlPurifier::process($message, [
            'HTML.Allowed' => 'a[href|title|target|rel],br,strong,em,b,i,u,span',
            'Attr.AllowedFrameTargets' => '_blank,_self,_parent,_top',
            'URI.AllowedSchemes' => [
                'http' => true,
                'https' => true,
                'mailto' => true,
                'tel' => true,
            ],
            'AutoFormat.RemoveEmpty' => true,
        ]);

        return nl2br(trim($sanitized), false);
    }

    private function normalizeUrl(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }

        return UrlHelper::url(ltrim($trimmed, '/'));
    }

    private function settings(): Settings
    {
        /** @var Settings $settings */
        $settings = Plugin::getInstance()->getSettings();

        return $settings;
    }

    /** Register the external initializer in the current page response. */
    public function registerDeferredAssets(): void
    {
        Craft::$app->getView()->registerAssetBundle(DeferredWidgetAsset::class);
    }

    /** @param array<string, mixed>|null $options */
    private function resolveWidgetMode(?array $options): string
    {
        $mode = $options['mode'] ?? 'normal';

        return in_array($mode, ['normal', 'deferred', 'shell'], true) ? $mode : 'normal';
    }

    /**
     * Build the shared Twig variables and token-free client configuration.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function baseWidgetContext(
        array $data,
        string $type,
        string $actionRoute,
        bool $isLoggedIn,
        bool $guestAllowed,
    ): array {
        $siteId = $data['siteId'] ?? Craft::$app->getSites()->getCurrentSite()->id;
        $actionUrl = UrlHelper::actionUrl($actionRoute);
        $loginUrl = $this->resolveLoginUrl();
        $guestInteractionMode = $this->resolveGuestInteractionMode();
        $guestInteractionMessage = $this->resolveGuestInteractionMessage();
        $guestInteractionMessageHtml = $this->resolveGuestInteractionMessageHtml();

        return [
            'elementId' => $data['elementId'] ?? null,
            'fieldId' => $data['fieldId'] ?? null,
            'siteId' => $siteId,
            'actionUrl' => $actionUrl,
            'loginUrl' => $loginUrl,
            'guestInteractionMode' => $guestInteractionMode,
            'guestInteractionMessage' => $guestInteractionMessage,
            'guestInteractionMessageHtml' => $guestInteractionMessageHtml,
            'isLoggedIn' => $isLoggedIn,
            'guestAllowed' => $guestAllowed,
            'widgetConfig' => [
                'type' => $type,
                'elementId' => $data['elementId'] ?? null,
                'fieldId' => $data['fieldId'] ?? null,
                'siteId' => $siteId,
                'actionUrl' => $actionUrl,
                'csrfUrl' => UrlHelper::actionUrl('users/session-info'),
                'isLoggedIn' => $isLoggedIn,
                'guestAllowed' => $guestAllowed,
                'loginUrl' => $loginUrl,
                'guestInteractionMode' => $guestInteractionMode,
                'guestInteractionMessageHtml' => $guestInteractionMessageHtml,
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    private function widgetUid(string $type, array $data): string
    {
        return sprintf(
            'engagement-%s-%s-%s-%s',
            $type,
            $data['elementId'] ?? '0',
            $data['fieldId'] ?? '0',
            $data['siteId'] ?? Craft::$app->getSites()->getCurrentSite()->id,
        );
    }

    /**
     * @param array<string, mixed>|null $options
     * @return array{html?: string, css?: string, js?: string}
     */
    private function resolveWidgetTemplateOverrides(?array $options): array
    {
        if ($options === null) {
            return [];
        }

        $overrides = [];

        if (isset($options['widgetTemplates']) && is_array($options['widgetTemplates'])) {
            $source = $options['widgetTemplates'];

            if (isset($source['html']) && is_string($source['html']) && trim($source['html']) !== '') {
                $overrides['html'] = trim($source['html']);
            }
            if (isset($source['css']) && is_string($source['css']) && trim($source['css']) !== '') {
                $overrides['css'] = trim($source['css']);
            }
            if (isset($source['js']) && is_string($source['js']) && trim($source['js']) !== '') {
                $overrides['js'] = trim($source['js']);
            }
        }

        if (isset($options['htmlTemplate']) && is_string($options['htmlTemplate']) && trim($options['htmlTemplate']) !== '') {
            $overrides['html'] = trim($options['htmlTemplate']);
        } elseif (isset($options['html']) && is_string($options['html']) && trim($options['html']) !== '') {
            $overrides['html'] = trim($options['html']);
        }

        if (isset($options['cssTemplate']) && is_string($options['cssTemplate']) && trim($options['cssTemplate']) !== '') {
            $overrides['css'] = trim($options['cssTemplate']);
        } elseif (isset($options['css']) && is_string($options['css']) && trim($options['css']) !== '') {
            $overrides['css'] = trim($options['css']);
        }

        if (isset($options['jsTemplate']) && is_string($options['jsTemplate']) && trim($options['jsTemplate']) !== '') {
            $overrides['js'] = trim($options['jsTemplate']);
        } elseif (isset($options['js']) && is_string($options['js']) && trim($options['js']) !== '') {
            $overrides['js'] = trim($options['js']);
        }

        return $overrides;
    }

    /**
     * Render a plugin template while preserving the original template mode.
     *
     * @param array<string, mixed> $data
     */
    private function renderPluginTemplate(string $template, array $data): string
    {
        $view = Craft::$app->getView();
        $oldMode = $view->getTemplateMode();

        try {
            $view->setTemplateMode(View::TEMPLATE_MODE_CP);

            return $view->renderTemplate($template, $data);
        } finally {
            $view->setTemplateMode($oldMode);
        }
    }
}
