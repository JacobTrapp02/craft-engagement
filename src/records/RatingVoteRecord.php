<?php

namespace jtdev\craftengagement\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Active record for engagement_ratings_votes.
 *
 * @property int $id
 * @property int $aggregateId
 * @property int|null $userId
 * @property string|null $sessionId
 * @property int $rating
 * @property string $dateCreated
 * @property string $dateUpdated
 * @property string $uid
 * @property-read RatingAggregateRecord $aggregate
 */
class RatingVoteRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%engagement_ratings_votes}}';
    }

    public function getAggregate(): ActiveQueryInterface
    {
        return $this->hasOne(RatingAggregateRecord::class, ['id' => 'aggregateId']);
    }
}
