<?php

namespace jtdev\craftengagement\helpers;

use Craft;
use craft\elements\db\ElementQuery;
use jtdev\craftengagement\fields\FavoritesField;
use jtdev\craftengagement\fields\LikesField;
use jtdev\craftengagement\fields\RatingField;

/**
 * Shared query helpers for engagement field filtering/sorting.
 */
class EngagementQueryHelper
{
    private const ELEMENT_ID_SQL = 'elements.id';
    private const ELEMENT_SITE_ID_SQL = 'elements_sites.siteId';

    public static function toBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = strtolower(trim($value));

        return match ($normalized) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
        };
    }

    public static function toInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '' || !preg_match('/^-?\d+$/', $normalized)) {
            return null;
        }

        return (int)$normalized;
    }

    public static function toFloat(mixed $value): ?float
    {
        if (is_float($value) || is_int($value)) {
            return (float)$value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return (float)$normalized;
    }

    public static function ratingMetricSql(int $fieldId, string $metric, ?int $siteId = null): string
    {
        $siteSql = self::siteSql($siteId);
        $base = "FROM {{%engagement_ratings_aggregate}} rAgg " .
            "WHERE rAgg.elementId = " . self::ELEMENT_ID_SQL . " " .
            "AND rAgg.fieldId = $fieldId " .
            "AND rAgg.siteId = $siteSql";

        return match ($metric) {
            'average' => "COALESCE((SELECT CASE WHEN rAgg.voteCount > 0 THEN (rAgg.ratingSum * 1.0 / rAgg.voteCount) ELSE 0 END $base LIMIT 1), 0)",
            'voteCount' => "COALESCE((SELECT rAgg.voteCount $base LIMIT 1), 0)",
            default => '0',
        };
    }

    public static function likesMetricSql(int $fieldId, string $metric, ?int $siteId = null): string
    {
        $siteSql = self::siteSql($siteId);
        $base = "FROM {{%engagement_likes_aggregate}} lAgg " .
            "WHERE lAgg.elementId = " . self::ELEMENT_ID_SQL . " " .
            "AND lAgg.fieldId = $fieldId " .
            "AND lAgg.siteId = $siteSql";

        return match ($metric) {
            'likes' => "COALESCE((SELECT lAgg.likeCount $base LIMIT 1), 0)",
            'dislikes' => "COALESCE((SELECT lAgg.dislikeCount $base LIMIT 1), 0)",
            'totalVotes' => "COALESCE((SELECT (lAgg.likeCount + lAgg.dislikeCount) $base LIMIT 1), 0)",
            'score' => "COALESCE((SELECT (CAST(lAgg.likeCount AS SIGNED) - CAST(lAgg.dislikeCount AS SIGNED)) $base LIMIT 1), 0)",
            default => '0',
        };
    }

    public static function favoritesMetricSql(int $fieldId, string $metric, ?int $siteId = null): string
    {
        $siteSql = self::siteSql($siteId);
        $base = "FROM {{%engagement_favorites_aggregate}} fAgg " .
            "WHERE fAgg.elementId = " . self::ELEMENT_ID_SQL . " " .
            "AND fAgg.fieldId = $fieldId " .
            "AND fAgg.siteId = $siteSql";

        return match ($metric) {
            'count' => "COALESCE((SELECT fAgg.favoriteCount $base LIMIT 1), 0)",
            default => '0',
        };
    }

    public static function rewriteOrderBy(ElementQuery $query): void
    {
        $orderBy = $query->orderBy;
        if ($orderBy === null || $orderBy === [] || $orderBy === '') {
            return;
        }

        if (is_string($orderBy)) {
            $rewritten = self::rewriteOrderByString($orderBy);
            if ($rewritten !== $orderBy) {
                $query->orderBy($rewritten);
            }

            return;
        }

        if (!is_array($orderBy)) {
            return;
        }

        $rewritten = [];
        foreach ($orderBy as $column => $direction) {
            if (!is_string($column)) {
                $rewritten[$column] = $direction;
                continue;
            }

            $rewrittenColumn = self::rewriteSortToken($column);
            $rewritten[$rewrittenColumn] = $direction;
        }

        if ($rewritten !== $orderBy) {
            $query->orderBy($rewritten);
        }
    }

    private static function rewriteOrderByString(string $orderBy): string
    {
        return (string)preg_replace_callback(
            '/\b([A-Za-z_][A-Za-z0-9_]*)__([A-Za-z][A-Za-z0-9_]*)\b/',
            static fn(array $matches): string => self::rewriteSortToken($matches[0]),
            $orderBy
        );
    }

    private static function rewriteSortToken(string $token): string
    {
        if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)__([A-Za-z][A-Za-z0-9_]*)$/', $token, $matches)) {
            return $token;
        }

        $handle = $matches[1];
        $metric = $matches[2];

        $field = Craft::$app->getFields()->getFieldByHandle($handle);
        if ($field === null || $field->id === null) {
            return $token;
        }

        if ($field instanceof RatingField) {
            if (!in_array($metric, ['average', 'voteCount'], true)) {
                return $token;
            }

            return self::ratingMetricSql((int)$field->id, $metric);
        }

        if ($field instanceof LikesField) {
            if (!in_array($metric, ['likes', 'dislikes', 'score', 'totalVotes'], true)) {
                return $token;
            }

            return self::likesMetricSql((int)$field->id, $metric);
        }

        if ($field instanceof FavoritesField) {
            if ($metric !== 'count') {
                return $token;
            }

            return self::favoritesMetricSql((int)$field->id, $metric);
        }

        return $token;
    }

    private static function siteSql(?int $siteId = null): string
    {
        return $siteId === null ? self::ELEMENT_SITE_ID_SQL : (string)(int)$siteId;
    }
}
