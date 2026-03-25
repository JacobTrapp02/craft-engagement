<?php

namespace jtdev\craftengagement\console\controllers;

use craft\console\Controller;
use jtdev\craftengagement\Plugin;
use yii\console\ExitCode;

/**
 * Console tools for recounting aggregate totals.
 */
class RecountController extends Controller
{
    public bool $all = false;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'all';

        return $options;
    }

    /**
     * Recount a single ratings aggregate by ID.
     *
     * Usage:
     * php craft engagement/recount/rating 123
     * php craft engagement/recount/rating --all
     */
    public function actionRating(?int $id = null): int
    {
        $aggregateService = Plugin::getInstance()->ratingAggregates;

        if ($this->all) {
            $aggregates = $aggregateService->getMany([], ['id' => SORT_ASC]);
            $total = count($aggregates);
            $changed = 0;

            foreach ($aggregates as $aggregate) {
                $beforeVoteCount = $aggregate->voteCount;
                $beforeRatingSum = $aggregate->ratingSum;
                $beforeAverage = $aggregate->average;

                $after = $aggregateService->forceRecount($aggregate->id);
                if ($after === null) {
                    continue;
                }

                $isChanged = $beforeVoteCount !== $after->voteCount || $beforeRatingSum !== $after->ratingSum;
                if ($isChanged) {
                    $changed++;
                }

                $status = $isChanged ? 'UPDATED' : 'UNCHANGED';
                $this->stdout(
                    "[{$status}] ratings aggregate {$aggregate->id}: " .
                    "voteCount {$beforeVoteCount} -> {$after->voteCount}, " .
                    "ratingSum {$beforeRatingSum} -> {$after->ratingSum}, " .
                    "average {$beforeAverage} -> {$after->average}\n"
                );
            }

            $this->stdout("Processed {$total} ratings aggregates. Updated {$changed}.\n");

            return ExitCode::OK;
        }

        if ($id === null) {
            $this->stderr("Please provide an aggregate ID, or pass --all.\n");

            return ExitCode::DATAERR;
        }

        $before = $aggregateService->getById($id);
        if ($before === null) {
            $this->stderr("Ratings aggregate {$id} was not found.\n");

            return ExitCode::DATAERR;
        }

        $after = $aggregateService->forceRecount($id);
        if ($after === null) {
            $this->stderr("Ratings aggregate {$id} could not be recounted.\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout(
            "Recounted ratings aggregate {$id}: " .
            "voteCount {$before->voteCount} -> {$after->voteCount}, " .
            "ratingSum {$before->ratingSum} -> {$after->ratingSum}, " .
            "average {$before->average} -> {$after->average}\n"
        );

        return ExitCode::OK;
    }

    /**
     * Recount a single likes aggregate by ID.
     *
     * Usage:
     * php craft engagement/recount/like 123
     * php craft engagement/recount/like --all
     */
    public function actionLike(?int $id = null): int
    {
        $aggregateService = Plugin::getInstance()->likesAggregates;

        if ($this->all) {
            $aggregates = $aggregateService->getMany([], ['id' => SORT_ASC]);
            $total = count($aggregates);
            $changed = 0;

            foreach ($aggregates as $aggregate) {
                $beforeLikeCount = $aggregate->likeCount;
                $beforeDislikeCount = $aggregate->dislikeCount;

                $after = $aggregateService->forceRecount($aggregate->id);
                if ($after === null) {
                    continue;
                }

                $isChanged = $beforeLikeCount !== $after->likeCount || $beforeDislikeCount !== $after->dislikeCount;
                if ($isChanged) {
                    $changed++;
                }

                $status = $isChanged ? 'UPDATED' : 'UNCHANGED';
                $this->stdout(
                    "[{$status}] likes aggregate {$aggregate->id}: " .
                    "likeCount {$beforeLikeCount} -> {$after->likeCount}, " .
                    "dislikeCount {$beforeDislikeCount} -> {$after->dislikeCount}\n"
                );
            }

            $this->stdout("Processed {$total} likes aggregates. Updated {$changed}.\n");

            return ExitCode::OK;
        }

        if ($id === null) {
            $this->stderr("Please provide an aggregate ID, or pass --all.\n");

            return ExitCode::DATAERR;
        }

        $before = $aggregateService->getById($id);
        if ($before === null) {
            $this->stderr("Likes aggregate {$id} was not found.\n");

            return ExitCode::DATAERR;
        }

        $after = $aggregateService->forceRecount($id);
        if ($after === null) {
            $this->stderr("Likes aggregate {$id} could not be recounted.\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout(
            "Recounted likes aggregate {$id}: " .
            "likeCount {$before->likeCount} -> {$after->likeCount}, " .
            "dislikeCount {$before->dislikeCount} -> {$after->dislikeCount}\n"
        );

        return ExitCode::OK;
    }

    /**
     * Recount a single favorites aggregate by ID.
     *
     * Usage:
     * php craft engagement/recount/favorite 123
     * php craft engagement/recount/favorite --all
     */
    public function actionFavorite(?int $id = null): int
    {
        $aggregateService = Plugin::getInstance()->favoritesAggregates;

        if ($this->all) {
            $aggregates = $aggregateService->getMany([], ['id' => SORT_ASC]);
            $total = count($aggregates);
            $changed = 0;

            foreach ($aggregates as $aggregate) {
                $beforeFavoriteCount = $aggregate->favoriteCount;

                $after = $aggregateService->forceRecount($aggregate->id);
                if ($after === null) {
                    continue;
                }

                $isChanged = $beforeFavoriteCount !== $after->favoriteCount;
                if ($isChanged) {
                    $changed++;
                }

                $status = $isChanged ? 'UPDATED' : 'UNCHANGED';
                $this->stdout(
                    "[{$status}] favorites aggregate {$aggregate->id}: " .
                    "favoriteCount {$beforeFavoriteCount} -> {$after->favoriteCount}\n"
                );
            }

            $this->stdout("Processed {$total} favorites aggregates. Updated {$changed}.\n");

            return ExitCode::OK;
        }

        if ($id === null) {
            $this->stderr("Please provide an aggregate ID, or pass --all.\n");

            return ExitCode::DATAERR;
        }

        $before = $aggregateService->getById($id);
        if ($before === null) {
            $this->stderr("Favorites aggregate {$id} was not found.\n");

            return ExitCode::DATAERR;
        }

        $after = $aggregateService->forceRecount($id);
        if ($after === null) {
            $this->stderr("Favorites aggregate {$id} could not be recounted.\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout(
            "Recounted favorites aggregate {$id}: " .
            "favoriteCount {$before->favoriteCount} -> {$after->favoriteCount}\n"
        );

        return ExitCode::OK;
    }

    /**
     * Alias for `rating`.
     */
    public function actionRatings(?int $id = null): int
    {
        return $this->actionRating($id);
    }

    /**
     * Alias for `like`.
     */
    public function actionLikes(?int $id = null): int
    {
        return $this->actionLike($id);
    }

    /**
     * Alias for `favorite`.
     */
    public function actionFavorites(?int $id = null): int
    {
        return $this->actionFavorite($id);
    }
}
