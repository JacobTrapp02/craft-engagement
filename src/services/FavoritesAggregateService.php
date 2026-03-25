<?php

namespace jtdev\craftengagement\services;

use craft\base\Component;
use jtdev\craftengagement\models\FavoritesAggregate;
use jtdev\craftengagement\Plugin;
use jtdev\craftengagement\records\FavoritesAggregateRecord;

/**
 * Basic CRUD service for favorites aggregates.
 */
class FavoritesAggregateService extends Component
{
    public function getById(int $id): ?FavoritesAggregate
    {
        $record = FavoritesAggregateRecord::findOne($id);

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * Fetch a single aggregate for an element/field/site triplet.
     */
    public function getByElementFieldSite(int $elementId, int $fieldId, int $siteId): ?FavoritesAggregate
    {
        $record = FavoritesAggregateRecord::find()
            ->where([
                'elementId' => $elementId,
                'fieldId' => $fieldId,
                'siteId' => $siteId,
            ])
            ->one();

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * @return FavoritesAggregate[]
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
     * @return FavoritesAggregate[]
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
     * @return FavoritesAggregate[]
     */
    public function getBySiteId(int $siteId): array
    {
        return $this->getMany(['siteId' => $siteId]);
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, int>|null $orderBy
     * @return FavoritesAggregate[]
     */
    public function getMany(
        array $criteria = [],
        ?array $orderBy = ['id' => SORT_DESC],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = FavoritesAggregateRecord::find()->where($criteria);

        if ($orderBy !== null) {
            $query->orderBy($orderBy);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        /** @var FavoritesAggregateRecord[] $records */
        $records = $query->all();

        return array_map([$this, 'recordToModel'], $records);
    }

    public function add(FavoritesAggregate $aggregate): ?FavoritesAggregate
    {
        $record = new FavoritesAggregateRecord();

        $record->elementId = $aggregate->elementId;
        $record->fieldId = $aggregate->fieldId;
        $record->siteId = $aggregate->siteId;
        $record->favoriteCount = $aggregate->favoriteCount;

        if (!$record->save()) {
            return null;
        }

        return $this->recordToModel($record);
    }

    public function update(int $id, array $attributes): ?FavoritesAggregate
    {
        $record = FavoritesAggregateRecord::findOne($id);

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
        $record = FavoritesAggregateRecord::findOne($id);

        if (!$record) {
            return false;
        }

        return (bool)$record->delete();
    }

    public function forceRecount(int $aggregateId): ?FavoritesAggregate
    {
        $aggregate = $this->getById($aggregateId);
        if ($aggregate === null) {
            return null;
        }

        $entries = Plugin::getInstance()->favoritesEntries->getByAggregateId($aggregateId);

        return $this->update($aggregateId, [
            'favoriteCount' => count($entries),
        ]);
    }

    private function recordToModel(FavoritesAggregateRecord $record): FavoritesAggregate
    {
        return new FavoritesAggregate([
            'id' => (int)$record->id,
            'elementId' => (int)$record->elementId,
            'fieldId' => (int)$record->fieldId,
            'siteId' => (int)$record->siteId,
            'favoriteCount' => (int)$record->favoriteCount,
        ]);
    }
}
