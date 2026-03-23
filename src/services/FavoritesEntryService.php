<?php

namespace jtdev\craftengagement\services;

use craft\base\Component;
use jtdev\craftengagement\models\FavoritesEntry;
use jtdev\craftengagement\records\FavoritesEntryRecord;

/**
 * Basic CRUD service for favorite entries.
 */
class FavoritesEntryService extends Component
{
    public function getById(int $id): ?FavoritesEntry
    {
        $record = FavoritesEntryRecord::findOne($id);

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * @return FavoritesEntry[]
     */
    public function getByAggregateId(int $aggregateId): array
    {
        return $this->getMany(['aggregateId' => $aggregateId]);
    }

    /**
     * @return FavoritesEntry[]
     */
    public function getByUserId(int $userId): array
    {
        return $this->getMany(['userId' => $userId]);
    }

    /**
     * @return FavoritesEntry[]
     */
    public function getBySessionId(string $sessionId): array
    {
        return $this->getMany(['sessionId' => $sessionId]);
    }

    public function getByAggregateAndUserId(int $aggregateId, int $userId): ?FavoritesEntry
    {
        $record = FavoritesEntryRecord::find()
            ->where([
                'aggregateId' => $aggregateId,
                'userId' => $userId,
            ])
            ->one();

        return $record ? $this->recordToModel($record) : null;
    }

    public function getByAggregateAndSessionId(int $aggregateId, string $sessionId): ?FavoritesEntry
    {
        $record = FavoritesEntryRecord::find()
            ->where([
                'aggregateId' => $aggregateId,
                'sessionId' => $sessionId,
            ])
            ->one();

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, int>|null $orderBy
     * @return FavoritesEntry[]
     */
    public function getMany(
        array $criteria = [],
        ?array $orderBy = ['id' => SORT_DESC],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = FavoritesEntryRecord::find()->where($criteria);

        if ($orderBy !== null) {
            $query->orderBy($orderBy);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        /** @var FavoritesEntryRecord[] $records */
        $records = $query->all();

        return array_map([$this, 'recordToModel'], $records);
    }

    public function add(FavoritesEntry $entry): ?FavoritesEntry
    {
        $record = new FavoritesEntryRecord();

        $record->aggregateId = $entry->aggregateId;
        $record->userId = $entry->userId;
        $record->sessionId = $entry->sessionId;

        if (!$record->save()) {
            return null;
        }

        return $this->recordToModel($record);
    }

    public function update(int $id, array $attributes): ?FavoritesEntry
    {
        $record = FavoritesEntryRecord::findOne($id);

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
        $record = FavoritesEntryRecord::findOne($id);

        if (!$record) {
            return false;
        }

        return (bool)$record->delete();
    }

    private function recordToModel(FavoritesEntryRecord $record): FavoritesEntry
    {
        return new FavoritesEntry([
            'id' => (int)$record->id,
            'aggregateId' => (int)$record->aggregateId,
            'userId' => $record->userId !== null ? (int)$record->userId : null,
            'sessionId' => $record->sessionId,
        ]);
    }
}
