<?php

namespace jtdev\craftengagement\web\assets;

use craft\web\AssetBundle;
use yii\web\View;

class DeferredWidgetAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/dist';

    public $js = [
        'engagement.js',
    ];

    public $jsOptions = [
        'position' => View::POS_END,
        'defer' => true,
    ];
}
