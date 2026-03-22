<?php

namespace jtdev\craftengagement\services;

use Craft;
use craft\base\Component;
use craft\helpers\UrlHelper;
use craft\web\View;
use jtdev\craftengagement\Plugin;
use jtdev\craftengagement\models\Rating;
use Twig\Markup;

/**
 * Twig rendering helpers exposed via craft.engagement.*
 */
class TwigService extends Component
{
    /**
     * Render a default favorite widget template from a field value.
     */
    public function renderFavorite(mixed $fieldValue): Markup|string
    {
        $data = $this->normalizeFieldData($fieldValue);
        if ($data === null) {
            return '';
        }

        $fieldName = null;
        if ($data['fieldId'] !== null) {
            $field = Craft::$app->getFields()->getFieldById((int)$data['fieldId']);
            $fieldName = $field?->name;
        }

        if (($data['enabled'] ?? true) === false) {
            return '';
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $isLoggedIn = $currentUser !== null;
        $userRating = null;
        if ($isLoggedIn && !empty($data['id'])) {
            $vote = Plugin::getInstance()->votes->getByAggregateAndUserId((int)$data['id'], (int)$currentUser->id);
            $userRating = $vote?->rating;
        }

        $html = $this->renderPluginTemplate(
            'engagement/_render/favorite.twig',
            [
                'elementId' => $data['elementId'],
                'fieldId' => $data['fieldId'],
                'siteId' => $data['siteId'] ?? Craft::$app->getSites()->getCurrentSite()->id,
                'label' => $fieldName ?? Craft::t('engagement', 'Favorite'),
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
                'isLoggedIn' => $isLoggedIn,
                'userRating' => $userRating,
                'loginUrl' => UrlHelper::url('login'),
                'actionUrl' => UrlHelper::actionUrl('engagement/ratings/cast-rating'),
            ]
        );

        return new Markup($html, Craft::$app->charset ?: 'UTF-8');
    }

    /**
     * @return array{id?: ?int, elementId: ?int, siteId: ?int, fieldId: ?int, enabled?: bool, scale?: int, icon?: string, emojiIcon?: string, customSvg?: ?string, average?: float, roundedAverage?: float, voteCount?: int, headingText?: ?string, clickToRateText?: ?string, yourRatingText?: ?string}|null
     */
    private function normalizeFieldData(mixed $fieldValue): ?array
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
            ];
        }

        return null;
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
