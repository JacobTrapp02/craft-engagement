<?php

namespace jtdev\craftengagement\models;

use craft\base\Model;
use DateTime;

/**
 * Favorites aggregate model.
 */
class FavoritesAggregate extends Model
{
    public ?int $id = null;
    public int $elementId;
    public int $fieldId;
    public int $siteId;
    public int $favoriteCount = 0;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;

    public function rules(): array
    {
        return [
            [['elementId', 'fieldId', 'siteId'], 'required'],
            [['id', 'elementId', 'fieldId', 'siteId', 'favoriteCount'], 'integer', 'min' => 0],
            [['dateCreated', 'dateUpdated'], 'safe'],
        ];
    }
}
