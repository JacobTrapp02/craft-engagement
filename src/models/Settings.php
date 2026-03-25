<?php

namespace jtdev\craftengagement\models;

use craft\base\Model;

/**
 * Engagement plugin settings.
 */
class Settings extends Model
{
    public const ICON_STAR = 'star';
    public const ICON_HEART = 'heart';
    public const ICON_THUMBS = 'thumbs';
    public const ICON_CUSTOM_SVG = 'customSvg';

    public string $loginUrl = 'login';
    public string $loginRedirectParam = 'redirect';
    public ?string $registerUrl = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            [['loginUrl'], 'required'],
            [['loginUrl', 'loginRedirectParam', 'registerUrl'], 'string'],
        ];
    }

    public function beforeValidate(): bool
    {
        $this->loginUrl = trim($this->loginUrl);
        $this->loginRedirectParam = trim($this->loginRedirectParam);
        $this->registerUrl = $this->registerUrl !== null ? trim($this->registerUrl) : null;
        if ($this->registerUrl === '') {
            $this->registerUrl = null;
        }

        return parent::beforeValidate();
    }
}
