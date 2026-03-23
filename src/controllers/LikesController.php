<?php

namespace jtdev\craftengagement\controllers;

use Craft;
use craft\web\Controller;
use jtdev\craftengagement\fields\LikesField;
use jtdev\craftengagement\models\LikesAggregate;
use jtdev\craftengagement\models\LikesVote;
use jtdev\craftengagement\Plugin;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Likes/dislikes endpoints.
 */
class LikesController extends Controller
{
    protected array|bool|int $allowAnonymous = ['cast-like'];

    /**
     * Cast or toggle a like/dislike vote.
     *
     * Required body params:
     * - elementId
     * - fieldId
     * - value (1 for like, -1 for dislike)
     *
     * Optional:
     * - siteId (defaults to current site)
     */
    public function actionCastLike(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();

        $elementId = (int)$request->getRequiredBodyParam('elementId');
        $fieldId = (int)$request->getRequiredBodyParam('fieldId');
        $siteId = (int)$request->getBodyParam('siteId', Craft::$app->getSites()->getCurrentSite()->id);
        $value = (int)$request->getRequiredBodyParam('value');

        if (!in_array($value, [-1, 1], true)) {
            throw new BadRequestHttpException('Like value must be 1 or -1.');
        }

        $field = Craft::$app->getFields()->getFieldById($fieldId);
        if (!$field instanceof LikesField) {
            throw new BadRequestHttpException('Invalid likes field.');
        }

        $element = Craft::$app->getElements()->getElementById($elementId, null, $siteId);
        if ($element === null) {
            throw new NotFoundHttpException('Element not found.');
        }

        /** @var mixed $fieldValue */
        $fieldValue = $element->getFieldValue($field->handle);
        $normalized = $fieldValue instanceof \jtdev\craftengagement\models\Like
            ? $fieldValue
            : $field->normalizeValue($fieldValue, $element);

        if (!$normalized->enabled) {
            throw new ForbiddenHttpException('Likes are disabled for this element.');
        }

        $aggregateService = Plugin::getInstance()->likesAggregates;
        $voteService = Plugin::getInstance()->likesVotes;

        $aggregate = $aggregateService->getByElementFieldSite($elementId, $fieldId, $siteId);
        if ($aggregate === null) {
            $aggregate = $aggregateService->add(new LikesAggregate([
                'elementId' => $elementId,
                'fieldId' => $fieldId,
                'siteId' => $siteId,
                'likeCount' => 0,
                'dislikeCount' => 0,
            ]));

            if ($aggregate === null) {
                $aggregate = $aggregateService->getByElementFieldSite($elementId, $fieldId, $siteId);
            }
        }

        if ($aggregate === null) {
            throw new BadRequestHttpException('Could not initialize likes aggregate.');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $sessionId = null;

        if ($currentUser === null) {
            if (!$normalized->allowGuestInteractions) {
                throw new ForbiddenHttpException('Guest likes are disabled for this field.');
            }
            $session = Craft::$app->getSession();
            $session->open();
            $sessionId = $session->getId();
        }

        $existingVote = $currentUser !== null
            ? $voteService->getByAggregateAndUserId($aggregate->id, (int)$currentUser->id)
            : $voteService->getByAggregateAndSessionId($aggregate->id, (string)$sessionId);

        if ($existingVote !== null && !$normalized->allowVoteChange) {
            throw new ForbiddenHttpException('Changing an existing like/dislike is disabled for this field.');
        }

        $savedVote = null;
        $userVote = null;

        if ($existingVote !== null && (int)$existingVote->value === $value) {
            $voteService->delete($existingVote->id);
        } elseif ($existingVote !== null) {
            $savedVote = $voteService->update($existingVote->id, ['value' => $value]);
            $userVote = $savedVote?->value;
        } else {
            $savedVote = $voteService->add(new LikesVote([
                'aggregateId' => $aggregate->id,
                'userId' => $currentUser !== null ? (int)$currentUser->id : null,
                'sessionId' => $currentUser === null ? (string)$sessionId : null,
                'value' => $value,
            ]));
            $userVote = $savedVote?->value;
        }

        $aggregate = $this->recalculateAggregate($aggregate->id);
        if ($aggregate === null) {
            throw new BadRequestHttpException('Could not recalculate likes aggregate.');
        }

        return $this->asJson([
            'success' => true,
            'aggregate' => [
                'id' => $aggregate->id,
                'elementId' => $aggregate->elementId,
                'fieldId' => $aggregate->fieldId,
                'siteId' => $aggregate->siteId,
                'likeCount' => $aggregate->likeCount,
                'dislikeCount' => $aggregate->dislikeCount,
            ],
            'vote' => [
                'id' => $savedVote?->id,
                'value' => $userVote,
            ],
        ]);
    }

    private function recalculateAggregate(int $aggregateId): ?LikesAggregate
    {
        $aggregateService = Plugin::getInstance()->likesAggregates;
        $voteService = Plugin::getInstance()->likesVotes;

        $aggregate = $aggregateService->getById($aggregateId);
        if ($aggregate === null) {
            return null;
        }

        $votes = $voteService->getByAggregateId($aggregateId);
        $likeCount = 0;
        $dislikeCount = 0;

        foreach ($votes as $vote) {
            if ((int)$vote->value === 1) {
                $likeCount++;
            } elseif ((int)$vote->value === -1) {
                $dislikeCount++;
            }
        }

        return $aggregateService->update($aggregateId, [
            'likeCount' => $likeCount,
            'dislikeCount' => $dislikeCount,
        ]);
    }
}
