<?php

namespace jtdev\craftengagement\models;

use craft\base\Model;
use DateTime;

/**
 * Individual rating vote model.
 */
class RatingVote extends Model
{
    public ?int $id = null;
    public int $aggregateId;
    public ?int $userId = null;
    public ?string $sessionId = null;
    public int $rating;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;

    public function rules(): array
    {
        return [
            [['aggregateId', 'rating'], 'required'],
            [['id', 'aggregateId', 'userId', 'rating'], 'integer', 'min' => 0],
            [['sessionId'], 'string', 'max' => 255],
            [['rating'], 'integer', 'min' => 1],
            [['dateCreated', 'dateUpdated'], 'safe'],
        ];
    }
}
