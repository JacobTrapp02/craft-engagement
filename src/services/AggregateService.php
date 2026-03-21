<?php

namespace jtdev\craftengagement\services;

use craft\base\Component;
use jtdev\craftengagement\models\Aggregate;
use jtdev\craftengagement\records\AggregateRecord;

/**
 * Basic CRUD service for rating aggregates.
 */
class AggregateService extends Component
{
    public function getById(int $id): ?Aggregate
    {
        $record = AggregateRecord::findOne($id);

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * Fetch a single aggregate for an element/field/site triplet.
     */
    public function getByElementFieldSite(int $elementId, int $fieldId, int $siteId): ?Aggregate
    {
        $record = AggregateRecord::find()
            ->where([
                'elementId' => $elementId,
                'fieldId' => $fieldId,
                'siteId' => $siteId,
            ])
            ->one();

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * @return Aggregate[]
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
     * @return Aggregate[]
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
     * @return Aggregate[]
     */
    public function getBySiteId(int $siteId): array
    {
        return $this->getMany(['siteId' => $siteId]);
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, int>|null $orderBy
     * @return Aggregate[]
     */
    public function getMany(
        array $criteria = [],
        ?array $orderBy = ['id' => SORT_DESC],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = AggregateRecord::find()->where($criteria);

        if ($orderBy !== null) {
            $query->orderBy($orderBy);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        /** @var AggregateRecord[] $records */
        $records = $query->all();

        return array_map([$this, 'recordToModel'], $records);
    }

    public function add(Aggregate $aggregate): ?Aggregate
    {
        $record = new AggregateRecord();

        $record->elementId = $aggregate->elementId;
        $record->fieldId = $aggregate->fieldId;
        $record->siteId = $aggregate->siteId;
        $record->average = $aggregate->average;
        $record->voteCount = $aggregate->voteCount;
        $record->scale = $aggregate->scale;

        if (!$record->save()) {
            return null;
        }

        return $this->recordToModel($record);
    }

    public function update(int $id, array $attributes): ?Aggregate
    {
        $record = AggregateRecord::findOne($id);

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
        $record = AggregateRecord::findOne($id);

        if (!$record) {
            return false;
        }

        return (bool)$record->delete();
    }

    private function recordToModel(AggregateRecord $record): Aggregate
    {
        return new Aggregate([
            'id' => (int)$record->id,
            'elementId' => (int)$record->elementId,
            'fieldId' => (int)$record->fieldId,
            'siteId' => (int)$record->siteId,
            'average' => (float)$record->average,
            'voteCount' => (int)$record->voteCount,
            'scale' => (int)$record->scale,
        ]);
    }
}
