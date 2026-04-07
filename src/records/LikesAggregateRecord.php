<?php

namespace jtdev\craftengagement\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Active record for engagement_likes_aggregate.
 *
 * @property int $id
 * @property int $elementId
 * @property int $fieldId
 * @property int $siteId
 * @property int $likeCount
 * @property int $dislikeCount
 * @property string $dateCreated
 * @property string $dateUpdated
 * @property string $uid
 * @property-read LikesVoteRecord[] $votes
 */
class LikesAggregateRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%engagement_likes_aggregate}}';
    }

    public function getVotes(): ActiveQueryInterface
    {
        return $this->hasMany(LikesVoteRecord::class, ['aggregateId' => 'id']);
    }
}
