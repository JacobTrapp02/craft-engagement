<?php

namespace jtdev\craftengagement\services;

use craft\base\Component;
use jtdev\craftengagement\models\RatingAggregate;
use jtdev\craftengagement\Plugin;
use jtdev\craftengagement\records\RatingAggregateRecord;

/**
 * Basic CRUD service for rating aggregates.
 */
class RatingAggregateService extends Component
{
    public function getById(int $id): ?RatingAggregate
    {
        $record = RatingAggregateRecord::findOne($id);

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * Fetch a single aggregate for an element/field/site triplet.
     */
    public function getByElementFieldSite(int $elementId, int $fieldId, int $siteId): ?RatingAggregate
    {
        $record = RatingAggregateRecord::find()
            ->where([
                'elementId' => $elementId,
                'fieldId' => $fieldId,
                'siteId' => $siteId,
            ])
            ->one();

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * @return RatingAggregate[]
     */
    public function getByElementId(int $elementId, ?int $siteId = null, ?int $fieldId = null): array
    {
        $criteria = ['elementId' => $elementId];

        if ($siteId !== null) {
            $criteria['siteId'] = $siteId;
        }

        if ($fieldId !== null) {
            $criteria['fieldId'] = $fieldId;
        }

        return $this->getMany($criteria);
    }

    /**
     * @return RatingAggregate[]
     */
    public function getByFieldId(int $fieldId, ?int $siteId = null): array
    {
        $criteria = ['fieldId' => $fieldId];

        if ($siteId !== null) {
            $criteria['siteId'] = $siteId;
        }

        return $this->getMany($criteria);
    }

    /**
     * @return RatingAggregate[]
     */
    public function getBySiteId(int $siteId): array
    {
        return $this->getMany(['siteId' => $siteId]);
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, int>|null $orderBy
     * @return RatingAggregate[]
     */
    public function getMany(
        array $criteria = [],
        ?array $orderBy = ['id' => SORT_DESC],
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        $query = RatingAggregateRecord::find()->where($criteria);

        if ($orderBy !== null) {
            $query->orderBy($orderBy);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        /** @var RatingAggregateRecord[] $records */
        $records = $query->all();

        return array_map([$this, 'recordToModel'], $records);
    }

    public function add(RatingAggregate $aggregate): ?RatingAggregate
    {
        $record = new RatingAggregateRecord();

        $record->elementId = $aggregate->elementId;
        $record->fieldId = $aggregate->fieldId;
        $record->siteId = $aggregate->siteId;
        $record->ratingSum = $aggregate->ratingSum;
        $record->voteCount = $aggregate->voteCount;
        $record->scale = $aggregate->scale;

        if (!$record->save()) {
            return null;
        }

        return $this->recordToModel($record);
    }

    public function update(int $id, array $attributes): ?RatingAggregate
    {
        $record = RatingAggregateRecord::findOne($id);

        if (!$record) {
            return null;
        }

        foreach ($attributes as $attribute => $value) {
            if ($record->hasAttribute($attribute)) {
                $record->{$attribute} = $value;
            }
        }

        if (!$record->save()) {
            return null;
        }

        return $this->recordToModel($record);
    }

    public function delete(int $id): bool
    {
        $record = RatingAggregateRecord::findOne($id);

        if (!$record) {
            return false;
        }

        return (bool)$record->delete();
    }

    public function forceRecount(int $aggregateId): ?RatingAggregate
    {
        $aggregate = $this->getById($aggregateId);
        if ($aggregate === null) {
            return null;
        }

        $votes = Plugin::getInstance()->ratingVotes->getByAggregateId($aggregateId);
        $count = count($votes);

        if ($count === 0) {
            return $this->update($aggregateId, [
                'ratingSum' => 0,
                'voteCount' => 0,
            ]);
        }

        $sum = 0;
        foreach ($votes as $vote) {
            $sum += (int)$vote->rating;
        }

        return $this->update($aggregateId, [
            'ratingSum' => $sum,
            'voteCount' => $count,
        ]);
    }

    private function recordToModel(RatingAggregateRecord $record): RatingAggregate
    {
        return new RatingAggregate([
            'id' => (int)$record->id,
            'elementId' => (int)$record->elementId,
            'fieldId' => (int)$record->fieldId,
            'siteId' => (int)$record->siteId,
            'ratingSum' => (int)$record->ratingSum,
            'voteCount' => (int)$record->voteCount,
            'scale' => (int)$record->scale,
        ]);
    }
}
