<?php

namespace jtdev\craftengagement\services;

use craft\base\Component;
use jtdev\craftengagement\models\Vote;
use jtdev\craftengagement\records\VoteRecord;

/**
 * Basic CRUD service for rating votes.
 */
class VoteService extends Component
{
    public function getById(int $id): ?Vote
    {
        $record = VoteRecord::findOne($id);

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * @return Vote[]
     */
    public function getByAggregateId(int $topId): array
    {
        return $this->getMany(['topId' => $topId]);
    }

    /**
     * @return Vote[]
     */
    public function getByUserId(int $userId): array
    {
        return $this->getMany(['userId' => $userId]);
    }

    /**
     * @return Vote[]
     */
    public function getBySessionId(string $sessionId): array
    {
        return $this->getMany(['sessionId' => $sessionId]);
    }

    public function getByAggregateAndUserId(int $topId, int $userId): ?Vote
    {
        $record = VoteRecord::find()
            ->where([
                'topId' => $topId,
                'userId' => $userId,
            ])
            ->one();

        return $record ? $this->recordToModel($record) : null;
    }

    public function getByAggregateAndSessionId(int $topId, string $sessionId): ?Vote
    {
        $record = VoteRecord::find()
            ->where([
                'topId' => $topId,
                'sessionId' => $sessionId,
            ])
            ->one();

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, int>|null $orderBy
     * @return Vote[]
     */
    public function getMany(
        array $criteria = [],
        ?array $orderBy = ['id' => SORT_DESC],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = VoteRecord::find()->where($criteria);

        if ($orderBy !== null) {
            $query->orderBy($orderBy);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        /** @var VoteRecord[] $records */
        $records = $query->all();

        return array_map([$this, 'recordToModel'], $records);
    }

    public function add(Vote $vote): ?Vote
    {
        $record = new VoteRecord();

        $record->topId = $vote->topId;
        $record->userId = $vote->userId;
        $record->sessionId = $vote->sessionId;
        $record->rating = $vote->rating;

        if (!$record->save()) {
            return null;
        }

        return $this->recordToModel($record);
    }

    public function update(int $id, array $attributes): ?Vote
    {
        $record = VoteRecord::findOne($id);

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
        $record = VoteRecord::findOne($id);

        if (!$record) {
            return false;
        }

        return (bool)$record->delete();
    }

    private function recordToModel(VoteRecord $record): Vote
    {
        return new Vote([
            'id' => (int)$record->id,
            'topId' => (int)$record->topId,
            'userId' => $record->userId !== null ? (int)$record->userId : null,
            'sessionId' => $record->sessionId,
            'rating' => (int)$record->rating,
        ]);
    }
}
