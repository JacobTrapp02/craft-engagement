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
    public function render(mixed $fieldValue, ?array $options = null): Markup|string
    {
        return Plugin::getInstance()->twig->render($fieldValue, $options);
    }

    /**
     * Render the default ratings UI snippet.
     */
    public function renderRating(mixed $fieldValue, ?array $options = null): Markup|string
    {
        return Plugin::getInstance()->twig->renderRating($fieldValue, $options);
    }

    /**
     * Render the default likes UI snippet.
     */
    public function renderLikes(mixed $fieldValue, ?array $options = null): Markup|string
    {
        return Plugin::getInstance()->twig->renderLikes($fieldValue, $options);
    }

    /**
     * Render the default favorites UI snippet.
     */
    public function renderFavorite(mixed $fieldValue, ?array $options = null): Markup|string
    {
        return Plugin::getInstance()->twig->renderFavorite($fieldValue, $options);
    }
}
