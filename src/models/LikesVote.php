<?php

namespace jtdev\craftengagement\models;

use craft\base\Model;
use DateTime;

/**
 * Individual likes/dislikes vote model.
 */
class LikesVote extends Model
{
    public ?int $id = null;
    public int $aggregateId;
    public ?int $userId = null;
    public ?string $sessionId = null;
    public int $value;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;

    public function rules(): array
    {
        return [
            [['aggregateId', 'value'], 'required'],
            [['id', 'aggregateId', 'userId'], 'integer', 'min' => 0],
            [['sessionId'], 'string', 'max' => 255],
            [['value'], 'in', 'range' => [-1, 1]],
            [['dateCreated', 'dateUpdated'], 'safe'],
            [['userId'], 'validateIdentity'],
        ];
    }

    public function validateIdentity(string $attribute, mixed $params): void
    {
        $hasUserId = $this->userId !== null;
        $hasSessionId = $this->sessionId !== null && trim($this->sessionId) !== '';

        if ($hasUserId === $hasSessionId) {
            $this->addError('userId', 'Exactly one of userId or sessionId must be set.');
            $this->addError('sessionId', 'Exactly one of userId or sessionId must be set.');
        }
    }
}
