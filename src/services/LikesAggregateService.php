<?php

namespace jtdev\craftengagement\services;

use craft\base\Component;
use jtdev\craftengagement\models\LikesAggregate;
use jtdev\craftengagement\Plugin;
use jtdev\craftengagement\records\LikesAggregateRecord;

/**
 * Basic CRUD service for likes/dislikes aggregates.
 */
class LikesAggregateService extends Component
{
    public function getById(int $id): ?LikesAggregate
    {
        $record = LikesAggregateRecord::findOne($id);

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * Fetch a single aggregate for an element/field/site triplet.
     */
    public function getByElementFieldSite(int $elementId, int $fieldId, int $siteId): ?LikesAggregate
    {
        $record = LikesAggregateRecord::find()
            ->where([
                'elementId' => $elementId,
                'fieldId' => $fieldId,
                'siteId' => $siteId,
            ])
            ->one();

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * @return LikesAggregate[]
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
     * @return LikesAggregate[]
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
     * @return LikesAggregate[]
     */
    public function getBySiteId(int $siteId): array
    {
        return $this->getMany(['siteId' => $siteId]);
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, int>|null $orderBy
     * @return LikesAggregate[]
     */
    public function getMany(
        array $criteria = [],
        ?array $orderBy = ['id' => SORT_DESC],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = LikesAggregateRecord::find()->where($criteria);

        if ($orderBy !== null) {
            $query->orderBy($orderBy);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        /** @var LikesAggregateRecord[] $records */
        $records = $query->all();

        return array_map([$this, 'recordToModel'], $records);
    }

    public function add(LikesAggregate $aggregate): ?LikesAggregate
    {
        $record = new LikesAggregateRecord();

        $record->elementId = $aggregate->elementId;
        $record->fieldId = $aggregate->fieldId;
        $record->siteId = $aggregate->siteId;
        $record->likeCount = $aggregate->likeCount;
        $record->dislikeCount = $aggregate->dislikeCount;

        if (!$record->save()) {
            return null;
        }

        return $this->recordToModel($record);
    }

    public function update(int $id, array $attributes): ?LikesAggregate
    {
        $record = LikesAggregateRecord::findOne($id);

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
        $record = LikesAggregateRecord::findOne($id);

        if (!$record) {
            return false;
        }

        return (bool)$record->delete();
    }

    public function forceRecount(int $aggregateId): ?LikesAggregate
    {
        $aggregate = $this->getById($aggregateId);
        if ($aggregate === null) {
            return null;
        }

        $votes = Plugin::getInstance()->likesVotes->getByAggregateId($aggregateId);
        $likeCount = 0;
        $dislikeCount = 0;

        foreach ($votes as $vote) {
            if ((int)$vote->value === 1) {
                $likeCount++;
            } elseif ((int)$vote->value === -1) {
                $dislikeCount++;
            }
        }

        return $this->update($aggregateId, [
            'likeCount' => $likeCount,
            'dislikeCount' => $dislikeCount,
        ]);
    }

    private function recordToModel(LikesAggregateRecord $record): LikesAggregate
    {
        return new LikesAggregate([
            'id' => (int)$record->id,
            'elementId' => (int)$record->elementId,
            'fieldId' => (int)$record->fieldId,
            'siteId' => (int)$record->siteId,
            'likeCount' => (int)$record->likeCount,
            'dislikeCount' => (int)$record->dislikeCount,
        ]);
    }
}
