<?php

namespace jtdev\craftengagement\controllers;

use Craft;
use craft\web\Controller;
use jtdev\craftengagement\fields\RatingField;
use jtdev\craftengagement\models\Aggregate;
use jtdev\craftengagement\models\Vote;
use jtdev\craftengagement\Plugin;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Ratings vote endpoints.
 */
class RatingsController extends Controller
{
    protected array|bool|int $allowAnonymous = ['cast-rating'];

    /**
     * Cast or update a vote for an element+field+site rating aggregate.
     *
     * Required body params:
     * - elementId
     * - fieldId
     * - rating
     *
     * Optional:
     * - siteId (defaults to current site)
     */
    public function actionCastRating(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();

        $elementId = (int)$request->getRequiredBodyParam('elementId');
        $fieldId = (int)$request->getRequiredBodyParam('fieldId');
        $siteId = (int)$request->getBodyParam('siteId', Craft::$app->getSites()->getCurrentSite()->id);
        $ratingValue = (int)$request->getRequiredBodyParam('rating');

        $field = Craft::$app->getFields()->getFieldById($fieldId);
        if (!$field instanceof RatingField) {
            throw new BadRequestHttpException('Invalid rating field.');
        }

        $element = Craft::$app->getElements()->getElementById($elementId, null, $siteId);
        if ($element === null) {
            throw new NotFoundHttpException('Element not found.');
        }

        /** @var mixed $fieldValue */
        $fieldValue = $element->getFieldValue($field->handle);
        $normalized = $fieldValue instanceof \jtdev\craftengagement\models\Rating
            ? $fieldValue
            : $field->normalizeValue($fieldValue, $element);

        if (!$normalized->enabled) {
            throw new ForbiddenHttpException('Ratings are disabled for this element.');
        }

        if ($ratingValue < 1 || $ratingValue > $normalized->scale) {
            throw new BadRequestHttpException('Rating must be between 1 and the configured scale.');
        }

        $aggregateService = Plugin::getInstance()->aggregates;
        $voteService = Plugin::getInstance()->votes;

        $aggregate = $aggregateService->getByElementFieldSite($elementId, $fieldId, $siteId);
        if ($aggregate === null) {
            $aggregate = $aggregateService->add(new Aggregate([
                'elementId' => $elementId,
                'fieldId' => $fieldId,
                'siteId' => $siteId,
                'average' => 0,
                'voteCount' => 0,
                'scale' => $normalized->scale,
            ]));

            // Handle race conditions where another request inserted it first.
            if ($aggregate === null) {
                $aggregate = $aggregateService->getByElementFieldSite($elementId, $fieldId, $siteId);
            }
        }

        if ($aggregate === null) {
            throw new BadRequestHttpException('Could not initialize aggregate.');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $existingVote = null;

        if ($currentUser !== null) {
            $existingVote = $voteService->getByAggregateAndUserId($aggregate->id, (int)$currentUser->id);
        } else {
            if (!$field->allowGuestRatings) {
                throw new ForbiddenHttpException('Guest ratings are disabled for this field.');
            }

            $session = Craft::$app->getSession();
            $session->open();
            $sessionId = $session->getId();
            $existingVote = $voteService->getByAggregateAndSessionId($aggregate->id, $sessionId);
        }

        if ($existingVote !== null && !$field->allowUserRatingChange) {
            throw new ForbiddenHttpException('Changing an existing rating is disabled for this field.');
        }

        if ($existingVote === null) {
            $newVote = new Vote([
                'topId' => $aggregate->id,
                'userId' => $currentUser !== null ? (int)$currentUser->id : null,
                'sessionId' => $currentUser === null ? Craft::$app->getSession()->getId() : null,
                'rating' => $ratingValue,
            ]);

            $savedVote = $voteService->add($newVote);
        } else {
            $savedVote = $voteService->update($existingVote->id, ['rating' => $ratingValue]);
        }

        if ($savedVote === null) {
            throw new BadRequestHttpException('Could not save vote.');
        }

        $aggregate = $this->recalculateAggregate($aggregate->id);

        if ($aggregate === null) {
            throw new BadRequestHttpException('Could not recalculate aggregate.');
        }

        return $this->asJson([
            'success' => true,
            'aggregate' => [
                'id' => $aggregate->id,
                'elementId' => $aggregate->elementId,
                'fieldId' => $aggregate->fieldId,
                'siteId' => $aggregate->siteId,
                'average' => $aggregate->average,
                'voteCount' => $aggregate->voteCount,
                'scale' => $aggregate->scale,
                'percentage' => $aggregate->percentage,
                'roundedAverage' => $aggregate->roundedAverage,
            ],
            'vote' => [
                'id' => $savedVote->id,
                'rating' => $savedVote->rating,
            ],
        ]);
    }

    private function recalculateAggregate(int $aggregateId): ?Aggregate
    {
        $aggregateService = Plugin::getInstance()->aggregates;
        $voteService = Plugin::getInstance()->votes;

        $aggregate = $aggregateService->getById($aggregateId);
        if ($aggregate === null) {
            return null;
        }

        $votes = $voteService->getByAggregateId($aggregateId);
        $count = count($votes);

        if ($count === 0) {
            return $aggregateService->update($aggregateId, [
                'average' => 0,
                'voteCount' => 0,
            ]);
        }

        $sum = 0;
        foreach ($votes as $vote) {
            $sum += (int)$vote->rating;
        }

        $average = round($sum / $count, 4);

        return $aggregateService->update($aggregateId, [
            'average' => $average,
            'voteCount' => $count,
        ]);
    }
}
