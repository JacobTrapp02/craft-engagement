<?php

namespace jtdev\craftengagement\models;

use craft\base\Model;

/**
 * Read model exposed by the likes field in Twig.
 */
class Like extends Model
{
    public bool $enabled = true;
    public ?int $id = null;
    public ?int $elementId = null;
    public ?int $fieldId = null;
    public ?int $siteId = null;
    public int $likeCount = 0;
    public int $dislikeCount = 0;
    public string $icon = Settings::ICON_THUMBS;
    /**
     * @deprecated Back-compat alias for legacy single-emoji config.
     */
    public ?string $emojiIcon = null;
    public string $likeEmojiIcon = '👍';
    public string $dislikeEmojiIcon = '👎';
    public string $beforeLikeColor = '';
    public string $afterLikeColor = '';
    public string $beforeDislikeColor = '';
    public string $afterDislikeColor = '';
    public ?string $customSvg = null;
    public bool $allowGuestInteractions = false;
    public bool $allowVoteChange = true;
    public ?string $headingText = null;
    public ?string $likeText = null;
    public ?string $dislikeText = null;
    public ?int $userVote = null;

    public function rules(): array
    {
        return [
            [['enabled', 'allowGuestInteractions', 'allowVoteChange'], 'boolean'],
            [['id', 'elementId', 'fieldId', 'siteId', 'likeCount', 'dislikeCount'], 'integer', 'min' => 0],
            [['icon', 'emojiIcon', 'likeEmojiIcon', 'dislikeEmojiIcon', 'beforeLikeColor', 'afterLikeColor', 'beforeDislikeColor', 'afterDislikeColor', 'customSvg', 'headingText', 'likeText', 'dislikeText'], 'string'],
            [['userVote'], 'in', 'range' => [-1, 0, 1]],
        ];
    }

    public function getTotalVotes(): int
    {
        return $this->likeCount + $this->dislikeCount;
    }

    public function getScore(): int
    {
        return $this->likeCount - $this->dislikeCount;
    }
}
