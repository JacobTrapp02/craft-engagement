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
     * Render UI by inferring the field value type automatically.
     */
    public function render(mixed $fieldValue): Markup|string
    {
        return Plugin::getInstance()->twig->render($fieldValue);
    }

    /**
     * Render the default ratings UI snippet.
     */
    public function renderRating(mixed $fieldValue): Markup|string
    {
        return Plugin::getInstance()->twig->renderRating($fieldValue);
    }

    /**
     * Render the default likes UI snippet.
     */
    public function renderLikes(mixed $fieldValue): Markup|string
    {
        return Plugin::getInstance()->twig->renderLikes($fieldValue);
    }

    /**
     * Render the default favorites UI snippet.
     */
    public function renderFavorite(mixed $fieldValue): Markup|string
    {
        return Plugin::getInstance()->twig->renderFavorite($fieldValue);
    }
}
