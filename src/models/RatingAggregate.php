<?php

namespace jtdev\craftengagement\models;

use craft\base\Model;
use DateTime;

/**
 * Ratings aggregate model.
 */
class RatingAggregate extends Model
{
    public ?int $id = null;
    public int $elementId;
    public int $fieldId;
    public int $siteId;
    public int $ratingSum = 0;
    public int $voteCount = 0;
    public int $scale = 5;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;

    public function rules(): array
    {
        return [
            [['elementId', 'fieldId', 'siteId', 'scale'], 'required'],
            [['id', 'elementId', 'fieldId', 'siteId', 'ratingSum', 'voteCount', 'scale'], 'integer', 'min' => 0],
            [['scale'], 'integer', 'min' => 1, 'max' => 100],
            [['dateCreated', 'dateUpdated'], 'safe'],
        ];
    }

    public function getAverage(): float
    {
        if ($this->voteCount <= 0) {
            return 0.0;
        }

        return round($this->ratingSum / $this->voteCount, 4);
    }

    public function getPercentage(): float
    {
        if ($this->scale <= 0) {
            return 0.0;
        }

        return ($this->average / $this->scale) * 100;
    }

    public function getRoundedAverage(): float
    {
        return round($this->average, 1);
    }
}
