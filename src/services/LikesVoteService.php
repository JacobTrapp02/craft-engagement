<?php

namespace jtdev\craftengagement\services;

use craft\base\Component;
use jtdev\craftengagement\models\LikesVote;
use jtdev\craftengagement\records\LikesVoteRecord;

/**
 * Basic CRUD service for likes/dislikes votes.
 */
class LikesVoteService extends Component
{
    public function getById(int $id): ?LikesVote
    {
        $record = LikesVoteRecord::findOne($id);

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * @return LikesVote[]
     */
    public function getByAggregateId(int $aggregateId): array
    {
        return $this->getMany(['aggregateId' => $aggregateId]);
    }

    /**
     * @return LikesVote[]
     */
    public function getByUserId(int $userId): array
    {
        return $this->getMany(['userId' => $userId]);
    }

    /**
     * @return LikesVote[]
     */
    public function getBySessionId(string $sessionId): array
    {
        return $this->getMany(['sessionId' => $sessionId]);
    }

    public function getByAggregateAndUserId(int $aggregateId, int $userId): ?LikesVote
    {
        $record = LikesVoteRecord::find()
            ->where([
                'aggregateId' => $aggregateId,
                'userId' => $userId,
            ])
            ->one();

        return $record ? $this->recordToModel($record) : null;
    }

    public function getByAggregateAndSessionId(int $aggregateId, string $sessionId): ?LikesVote
    {
        $record = LikesVoteRecord::find()
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
     * @return LikesVote[]
     */
    public function getMany(
        array $criteria = [],
        ?array $orderBy = ['id' => SORT_DESC],
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        $query = LikesVoteRecord::find()->where($criteria);

        if ($orderBy !== null) {
            $query->orderBy($orderBy);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        /** @var LikesVoteRecord[] $records */
        $records = $query->all();

        return array_map([$this, 'recordToModel'], $records);
    }

    public function add(LikesVote $vote): ?LikesVote
    {
        $record = new LikesVoteRecord();

        $record->aggregateId = $vote->aggregateId;
        $record->userId = $vote->userId;
        $record->sessionId = $vote->sessionId;
        $record->value = $vote->value;

        if (!$record->save()) {
            return null;
        }

        return $this->recordToModel($record);
    }

    public function update(int $id, array $attributes): ?LikesVote
    {
        $record = LikesVoteRecord::findOne($id);

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
        $record = LikesVoteRecord::findOne($id);

        if (!$record) {
            return false;
        }

        return (bool)$record->delete();
    }

    private function recordToModel(LikesVoteRecord $record): LikesVote
    {
        return new LikesVote([
            'id' => (int)$record->id,
            'aggregateId' => (int)$record->aggregateId,
            'userId' => $record->userId !== null ? (int)$record->userId : null,
            'sessionId' => $record->sessionId,
            'value' => (int)$record->value,
        ]);
    }
}
