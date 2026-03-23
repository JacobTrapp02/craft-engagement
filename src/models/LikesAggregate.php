<?php

namespace jtdev\craftengagement\models;

use craft\base\Model;
use DateTime;

/**
 * Likes/dislikes aggregate model.
 */
class LikesAggregate extends Model
{
    public ?int $id = null;
    public int $elementId;
    public int $fieldId;
    public int $siteId;
    public int $likeCount = 0;
    public int $dislikeCount = 0;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;

    public function rules(): array
    {
        return [
            [['elementId', 'fieldId', 'siteId'], 'required'],
            [['id', 'elementId', 'fieldId', 'siteId', 'likeCount', 'dislikeCount'], 'integer', 'min' => 0],
            [['dateCreated', 'dateUpdated'], 'safe'],
        ];
    }
}
