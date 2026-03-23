<?php

namespace jtdev\craftengagement\models;

use craft\base\Model;
use DateTime;

/**
 * Individual favorite toggle entry model.
 */
class FavoritesEntry extends Model
{
    public ?int $id = null;
    public int $aggregateId;
    public ?int $userId = null;
    public ?string $sessionId = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;

    public function rules(): array
    {
        return [
            [['aggregateId'], 'required'],
            [['id', 'aggregateId', 'userId'], 'integer', 'min' => 0],
            [['sessionId'], 'string', 'max' => 255],
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
