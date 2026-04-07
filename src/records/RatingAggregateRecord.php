<?php

namespace jtdev\craftengagement\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Active record for engagement_ratings_aggregate.
 *
 * @property int $id
 * @property int $elementId
 * @property int $fieldId
 * @property int $siteId
 * @property int $ratingSum
 * @property int $voteCount
 * @property int $scale
 * @property string $dateCreated
 * @property string $dateUpdated
 * @property string $uid
 * @property-read RatingVoteRecord[] $votes
 */
class RatingAggregateRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%engagement_ratings_aggregate}}';
    }

    public function getVotes(): ActiveQueryInterface
    {
        return $this->hasMany(RatingVoteRecord::class, ['aggregateId' => 'id']);
    }
}
