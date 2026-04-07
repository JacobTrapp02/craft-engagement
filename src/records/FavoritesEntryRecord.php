<?php

namespace jtdev\craftengagement\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Active record for engagement_favorites_entries.
 *
 * @property int $id
 * @property int $aggregateId
 * @property int|null $userId
 * @property string|null $sessionId
 * @property string $dateCreated
 * @property string $dateUpdated
 * @property string $uid
 * @property-read FavoritesAggregateRecord $aggregate
 */
class FavoritesEntryRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%engagement_favorites_entries}}';
    }

    public function getAggregate(): ActiveQueryInterface
    {
        return $this->hasOne(FavoritesAggregateRecord::class, ['id' => 'aggregateId']);
    }
}
