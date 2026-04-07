<?php

namespace jtdev\craftengagement\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Active record for engagement_likes_votes.
 *
 * @property int $id
 * @property int $aggregateId
 * @property int|null $userId
 * @property string|null $sessionId
 * @property int $value
 * @property string $dateCreated
 * @property string $dateUpdated
 * @property string $uid
 * @property-read LikesAggregateRecord $aggregate
 */
class LikesVoteRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%engagement_likes_votes}}';
    }

    public function getAggregate(): ActiveQueryInterface
    {
        return $this->hasOne(LikesAggregateRecord::class, ['id' => 'aggregateId']);
    }
}
