<?php

namespace jtdev\craftengagement\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Active record for engagement_favorites_aggregate.
 *
 * @property int $id
 * @property int $elementId
 * @property int $fieldId
 * @property int $siteId
 * @property int $favoriteCount
 * @property string $dateCreated
 * @property string $dateUpdated
 * @property string $uid
 * @property-read FavoritesEntryRecord[] $entries
 */
class FavoritesAggregateRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%engagement_favorites_aggregate}}';
    }

    public function getEntries(): ActiveQueryInterface
    {
        return $this->hasMany(FavoritesEntryRecord::class, ['aggregateId' => 'id']);
    }
}
