<?php

namespace jtdev\craftengagement\controllers;

use Craft;
use craft\web\Controller;
use jtdev\craftengagement\fields\FavoritesField;
use jtdev\craftengagement\models\FavoritesAggregate;
use jtdev\craftengagement\models\FavoritesEntry;
use jtdev\craftengagement\Plugin;
use jtdev\craftengagement\records\FavoritesAggregateRecord;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Favorites endpoints.
 */
class FavoritesController extends Controller
{
    protected array|bool|int $allowAnonymous = ['toggle-favorite'];

    /**
     * Toggle favorite on/off for an element+field+site row.
     */
    public function actionToggleFavorite(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();

        $elementId = (int)$request->getRequiredBodyParam('elementId');
        $fieldId = (int)$request->getRequiredBodyParam('fieldId');
        $siteId = (int)$request->getBodyParam('siteId', Craft::$app->getSites()->getCurrentSite()->id);

        $field = Craft::$app->getFields()->getFieldById($fieldId);
        if (!$field instanceof FavoritesField) {
            throw new BadRequestHttpException('Invalid favorites field.');
        }

        $element = Craft::$app->getElements()->getElementById($elementId, null, $siteId);
        if ($element === null) {
            throw new NotFoundHttpException('Element not found.');
        }

        /** @var mixed $fieldValue */
        $fieldValue = $element->getFieldValue($field->handle);
        $normalized = $fieldValue instanceof \jtdev\craftengagement\models\Favorite
            ? $fieldValue
            : $field->normalizeValue($fieldValue, $element);

        if (!$normalized->enabled) {
            throw new ForbiddenHttpException('Favorites are disabled for this element.');
        }

        $aggregateService = Plugin::getInstance()->favoritesAggregates;
        $entryService = Plugin::getInstance()->favoritesEntries;

        $aggregate = $aggregateService->getByElementFieldSite($elementId, $fieldId, $siteId);
        if ($aggregate === null) {
            $aggregate = $aggregateService->add(new FavoritesAggregate([
                'elementId' => $elementId,
                'fieldId' => $fieldId,
                'siteId' => $siteId,
                'favoriteCount' => 0,
            ]));

            if ($aggregate === null) {
                $aggregate = $aggregateService->getByElementFieldSite($elementId, $fieldId, $siteId);
            }
        }

        if ($aggregate === null) {
            throw new BadRequestHttpException('Could not initialize favorites aggregate.');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $sessionId = null;

        if ($currentUser === null) {
            if (!$normalized->allowGuestInteractions) {
                throw new ForbiddenHttpException('Guest favorites are disabled for this field.');
            }
            $session = Craft::$app->getSession();
            $session->open();
            $sessionId = $session->getId();
        }

        $existingEntry = $currentUser !== null
            ? $entryService->getByAggregateAndUserId($aggregate->id, (int)$currentUser->id)
            : $entryService->getByAggregateAndSessionId($aggregate->id, (string)$sessionId);

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $savedEntry = null;
            $isFavorited = false;
            $favoriteCountDelta = 0;

            if ($existingEntry !== null) {
                $entryService->delete($existingEntry->id);
                $isFavorited = false;
                $favoriteCountDelta = -1;
            } else {
                $savedEntry = $entryService->add(new FavoritesEntry([
                    'aggregateId' => $aggregate->id,
                    'userId' => $currentUser !== null ? (int)$currentUser->id : null,
                    'sessionId' => $currentUser === null ? (string)$sessionId : null,
                ]));
                if ($savedEntry === null) {
                    throw new BadRequestHttpException('Could not save favorite entry.');
                }
                $isFavorited = $savedEntry !== null;
                $favoriteCountDelta = $isFavorited ? 1 : 0;
            }

            FavoritesAggregateRecord::updateAllCounters([
                'favoriteCount' => $favoriteCountDelta,
            ], ['id' => $aggregate->id]);
            $aggregate = $aggregateService->getById($aggregate->id);

            if ($aggregate === null) {
                throw new BadRequestHttpException('Could not update favorites aggregate.');
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
                'favoriteCount' => $aggregate->favoriteCount,
            ],
            'entry' => [
                'id' => $savedEntry?->id,
                'isFavorited' => $isFavorited,
            ],
        ]);
    }
}
