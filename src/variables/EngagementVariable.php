<?php

namespace jtdev\craftengagement\variables;

use jtdev\craftengagement\Plugin;
use Twig\Markup;

/**
 * Exposes plugin helpers to Twig as craft.engagement.*
 */
class EngagementVariable
{
    /**
     * Render the default favorite UI snippet.
     */
    public function renderFavorite(mixed $fieldValue): Markup|string
    {
        return Plugin::getInstance()->twig->renderFavorite($fieldValue);
    }
}
