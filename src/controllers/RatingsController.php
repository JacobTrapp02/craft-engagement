<?php

namespace jtdev\craftengagement\controllers;

use Craft;
use craft\web\Controller;
use jtdev\craftengagement\fields\RatingField;
use jtdev\craftengagement\models\RatingAggregate;
use jtdev\craftengagement\models\RatingVote;
use jtdev\craftengagement\Plugin;
use jtdev\craftengagement\records\RatingAggregateRecord;
use Throwable;
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

        $aggregateService = Plugin::getInstance()->ratingAggregates;
        $voteService = Plugin::getInstance()->ratingVotes;

        $aggregate = $aggregateService->getByElementFieldSite($elementId, $fieldId, $siteId);
        if ($aggregate === null) {
            $aggregate = $aggregateService->add(new RatingAggregate([
                'elementId' => $elementId,
                'fieldId' => $fieldId,
                'siteId' => $siteId,
                'ratingSum' => 0,
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
            if (!$normalized->allowGuestRatings) {
                throw new ForbiddenHttpException('Guest ratings are disabled for this field.');
            }

            $session = Craft::$app->getSession();
            $session->open();
            $sessionId = $session->getId();
            $existingVote = $voteService->getByAggregateAndSessionId($aggregate->id, $sessionId);
        }

        if ($existingVote !== null && !$normalized->allowUserRatingChange) {
            throw new ForbiddenHttpException('Changing an existing rating is disabled for this field.');
        }

        $savedVote = null;
        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $voteCountDelta = 0;
            $ratingSumDelta = 0;

            if ($existingVote === null) {
                $newVote = new RatingVote([
                    'aggregateId' => $aggregate->id,
                    'userId' => $currentUser !== null ? (int)$currentUser->id : null,
                    'sessionId' => $currentUser === null ? Craft::$app->getSession()->getId() : null,
                    'rating' => $ratingValue,
                ]);

                $savedVote = $voteService->add($newVote);
                $voteCountDelta = 1;
                $ratingSumDelta = $ratingValue;
            } else {
                $savedVote = $voteService->update($existingVote->id, ['rating' => $ratingValue]);
                $ratingSumDelta = $ratingValue - (int)$existingVote->rating;
            }

            if ($savedVote === null) {
                throw new BadRequestHttpException('Could not save vote.');
            }

            if ($voteCountDelta !== 0 || $ratingSumDelta !== 0) {
                RatingAggregateRecord::updateAllCounters([
                    'voteCount' => $voteCountDelta,
                    'ratingSum' => $ratingSumDelta,
                ], ['id' => $aggregate->id]);
                $aggregate = $aggregateService->getById($aggregate->id);
            } else {
                $aggregate = $aggregateService->getById($aggregate->id);
            }

            if ($aggregate === null) {
                throw new BadRequestHttpException('Could not update aggregate.');
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
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
}
