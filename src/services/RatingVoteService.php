<?php

namespace jtdev\craftengagement\services;

use craft\base\Component;
use jtdev\craftengagement\models\RatingVote;
use jtdev\craftengagement\records\RatingVoteRecord;

/**
 * Basic CRUD service for rating votes.
 */
class RatingVoteService extends Component
{
    public function getById(int $id): ?RatingVote
    {
        $record = RatingVoteRecord::findOne($id);

        return $record ? $this->recordToModel($record) : null;
    }

    /**
     * @return RatingVote[]
     */
    public function getByAggregateId(int $topId): array
    {
        return $this->getMany(['topId' => $topId]);
    }

    /**
     * @return RatingVote[]
     */
    public function getByUserId(int $userId): array
    {
        return $this->getMany(['userId' => $userId]);
    }

    /**
     * @return RatingVote[]
     */
    public function getBySessionId(string $sessionId): array
    {
        return $this->getMany(['sessionId' => $sessionId]);
    }

    public function getByAggregateAndUserId(int $topId, int $userId): ?RatingVote
    {
        $record = RatingVoteRecord::find()
            ->where([
                'topId' => $topId,
                'userId' => $userId,
            ])
            ->one();

        return $record ? $this->recordToModel($record) : null;
    }

    public function getByAggregateAndSessionId(int $topId, string $sessionId): ?RatingVote
    {
        $record = RatingVoteRecord::find()
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
     * @return RatingVote[]
     */
    public function getMany(
        array $criteria = [],
        ?array $orderBy = ['id' => SORT_DESC],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = RatingVoteRecord::find()->where($criteria);

        if ($orderBy !== null) {
            $query->orderBy($orderBy);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        /** @var RatingVoteRecord[] $records */
        $records = $query->all();

        return array_map([$this, 'recordToModel'], $records);
    }

    public function add(RatingVote $vote): ?RatingVote
    {
        $record = new RatingVoteRecord();

        $record->topId = $vote->topId;
        $record->userId = $vote->userId;
        $record->sessionId = $vote->sessionId;
        $record->rating = $vote->rating;

        if (!$record->save()) {
            return null;
        }

        return $this->recordToModel($record);
    }

    public function update(int $id, array $attributes): ?RatingVote
    {
        $record = RatingVoteRecord::findOne($id);

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
        $record = RatingVoteRecord::findOne($id);

        if (!$record) {
            return false;
        }

        return (bool)$record->delete();
    }

    private function recordToModel(RatingVoteRecord $record): RatingVote
    {
        return new RatingVote([
            'id' => (int)$record->id,
            'topId' => (int)$record->topId,
            'userId' => $record->userId !== null ? (int)$record->userId : null,
            'sessionId' => $record->sessionId,
            'rating' => (int)$record->rating,
        ]);
    }
}
