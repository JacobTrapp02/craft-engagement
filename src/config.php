<?php

/**
 * Engagement plugin config.
 *
 * Copy this file to: `config/engagement.php` in your Craft project,
 * then adjust template handles to point at your site templates.
 */
return [
    // Max allowed value for rating scale inputs (clamped to 1..100).
    'maxScale' => 100,

    'widgetTemplates' => [
        'favorites' => [
            'html' => 'engagement/favorites/_render/widgethtml',
            'css' => 'engagement/favorites/_render/widgetcss',
            'js' => 'engagement/favorites/_render/widgetjs',
        ],
        'likes' => [
            'html' => 'engagement/likes/_render/widgethtml',
            'css' => 'engagement/likes/_render/widgetcss',
            'js' => 'engagement/likes/_render/widgetjs',
        ],
        'ratings' => [
            'html' => 'engagement/ratings/_render/widgethtml',
            'css' => 'engagement/ratings/_render/widgetcss',
            'js' => 'engagement/ratings/_render/widgetjs',
        ],
    ],
];
