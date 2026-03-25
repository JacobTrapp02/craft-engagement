<?php

namespace jtdev\craftengagement\controllers;

use Craft;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use jtdev\craftengagement\records\FavoritesAggregateRecord;
use jtdev\craftengagement\records\LikesAggregateRecord;
use jtdev\craftengagement\records\RatingAggregateRecord;
use yii\data\Pagination;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ModerationController extends Controller
{
    public function actionIndex(string $tab = 'all'): Response
    {
        $tab = strtolower($tab);
        $allowedTabs = ['all', 'ratings', 'likes', 'favorites'];
        if (!in_array($tab, $allowedTabs, true)) {
            throw new NotFoundHttpException('Invalid moderation tab.');
        }

        $request = Craft::$app->getRequest();
        $perPage = max(1, min(100, (int)$request->getQueryParam('perPage', 25)));
        $sort = (string)$request->getQueryParam('sort', 'recent');

        if ($tab === 'all') {
            $overviewLimit = 5;

            return $this->renderTemplate('engagement/moderation/index', [
                'tab' => 'all',
                'perPage' => $overviewLimit,
                'overviewLimit' => $overviewLimit,
                'ratings' => $this->loadRatings('recent', 0, $overviewLimit),
                'likes' => $this->loadLikes('recent', 0, $overviewLimit),
                'favorites' => $this->loadFavorites('recent', 0, $overviewLimit),
                'ratingsSort' => 'recent',
                'likesSort' => 'recent',
                'favoritesSort' => 'recent',
            ]);
        }

        if ($tab === 'ratings') {
            $query = RatingAggregateRecord::find();
            $this->applyRatingsSort($query, $sort);
            $total = (int)(clone $query)->count();
            $pagination = new Pagination([
                'totalCount' => $total,
                'defaultPageSize' => $perPage,
                'pageSize' => $perPage,
                'pageParam' => 'p',
            ]);

            return $this->renderTemplate('engagement/moderation/index', [
                'tab' => 'ratings',
                'perPage' => $perPage,
                'sort' => $sort,
                'pagination' => $pagination,
                'ratings' => $this->loadRatings($sort, $pagination->offset, $pagination->limit),
            ]);
        }

        if ($tab === 'likes') {
            $query = LikesAggregateRecord::find();
            $this->applyLikesSort($query, $sort);
            $total = (int)(clone $query)->count();
            $pagination = new Pagination([
                'totalCount' => $total,
                'defaultPageSize' => $perPage,
                'pageSize' => $perPage,
                'pageParam' => 'p',
            ]);

            return $this->renderTemplate('engagement/moderation/index', [
                'tab' => 'likes',
                'perPage' => $perPage,
                'sort' => $sort,
                'pagination' => $pagination,
                'likes' => $this->loadLikes($sort, $pagination->offset, $pagination->limit),
            ]);
        }

        $query = FavoritesAggregateRecord::find();
        $this->applyFavoritesSort($query, $sort);
        $total = (int)(clone $query)->count();
        $pagination = new Pagination([
            'totalCount' => $total,
            'defaultPageSize' => $perPage,
            'pageSize' => $perPage,
            'pageParam' => 'p',
        ]);

        return $this->renderTemplate('engagement/moderation/index', [
            'tab' => 'favorites',
            'perPage' => $perPage,
            'sort' => $sort,
            'pagination' => $pagination,
            'favorites' => $this->loadFavorites($sort, $pagination->offset, $pagination->limit),
        ]);
    }

    public function actionDetail(string $type, int $id): Response
    {
        $type = strtolower($type);
        if (!in_array($type, ['ratings', 'likes', 'favorites'], true)) {
            throw new NotFoundHttpException('Invalid moderation detail type.');
        }

        $request = Craft::$app->getRequest();
        $perPage = max(1, min(100, (int)$request->getQueryParam('perPage', 25)));

        if ($type === 'ratings') {
            $aggregate = RatingAggregateRecord::findOne($id);
            if ($aggregate === null) {
                throw new NotFoundHttpException('Ratings aggregate not found.');
            }

            $votesQuery = \jtdev\craftengagement\records\RatingVoteRecord::find()
                ->where(['aggregateId' => $id])
                ->orderBy(['id' => SORT_DESC]);
            $total = (int)(clone $votesQuery)->count();
            $pagination = new Pagination([
                'totalCount' => $total,
                'defaultPageSize' => $perPage,
                'pageSize' => $perPage,
                'pageParam' => 'p',
            ]);
            $votes = $votesQuery->offset($pagination->offset)->limit($pagination->limit)->all();

            $users = $this->usersById(array_map(static fn($vote): ?int => $vote->userId !== null ? (int)$vote->userId : null, $votes));

            return $this->renderTemplate('engagement/moderation/detail', [
                'type' => 'ratings',
                'aggregate' => $this->hydrateRatingAggregate($aggregate),
                'rows' => $votes,
                'users' => $users,
                'pagination' => $pagination,
            ]);
        }

        if ($type === 'likes') {
            $aggregate = LikesAggregateRecord::findOne($id);
            if ($aggregate === null) {
                throw new NotFoundHttpException('Likes aggregate not found.');
            }

            $votesQuery = \jtdev\craftengagement\records\LikesVoteRecord::find()
                ->where(['aggregateId' => $id])
                ->orderBy(['id' => SORT_DESC]);
            $total = (int)(clone $votesQuery)->count();
            $pagination = new Pagination([
                'totalCount' => $total,
                'defaultPageSize' => $perPage,
                'pageSize' => $perPage,
                'pageParam' => 'p',
            ]);
            $votes = $votesQuery->offset($pagination->offset)->limit($pagination->limit)->all();

            $users = $this->usersById(array_map(static fn($vote): ?int => $vote->userId !== null ? (int)$vote->userId : null, $votes));

            return $this->renderTemplate('engagement/moderation/detail', [
                'type' => 'likes',
                'aggregate' => $this->hydrateLikesAggregate($aggregate),
                'rows' => $votes,
                'users' => $users,
                'pagination' => $pagination,
            ]);
        }

        $aggregate = FavoritesAggregateRecord::findOne($id);
        if ($aggregate === null) {
            throw new NotFoundHttpException('Favorites aggregate not found.');
        }

        $entriesQuery = \jtdev\craftengagement\records\FavoritesEntryRecord::find()
            ->where(['aggregateId' => $id])
            ->orderBy(['id' => SORT_DESC]);
        $total = (int)(clone $entriesQuery)->count();
        $pagination = new Pagination([
            'totalCount' => $total,
            'defaultPageSize' => $perPage,
            'pageSize' => $perPage,
            'pageParam' => 'p',
        ]);
        $rows = $entriesQuery->offset($pagination->offset)->limit($pagination->limit)->all();

        $users = $this->usersById(array_map(static fn($row): ?int => $row->userId !== null ? (int)$row->userId : null, $rows));

        return $this->renderTemplate('engagement/moderation/detail', [
            'type' => 'favorites',
            'aggregate' => $this->hydrateFavoritesAggregate($aggregate),
            'rows' => $rows,
            'users' => $users,
            'pagination' => $pagination,
        ]);
    }

    private function loadRatings(string $sort, int $offset, int $limit): array
    {
        $query = RatingAggregateRecord::find();
        $this->applyRatingsSort($query, $sort);
        $records = $query->offset($offset)->limit($limit)->all();

        return array_map(fn(RatingAggregateRecord $record): array => $this->hydrateRatingAggregate($record), $records);
    }

    private function loadLikes(string $sort, int $offset, int $limit): array
    {
        $query = LikesAggregateRecord::find();
        $this->applyLikesSort($query, $sort);
        $records = $query->offset($offset)->limit($limit)->all();

        return array_map(fn(LikesAggregateRecord $record): array => $this->hydrateLikesAggregate($record), $records);
    }

    private function loadFavorites(string $sort, int $offset, int $limit): array
    {
        $query = FavoritesAggregateRecord::find();
        $this->applyFavoritesSort($query, $sort);
        $records = $query->offset($offset)->limit($limit)->all();

        return array_map(fn(FavoritesAggregateRecord $record): array => $this->hydrateFavoritesAggregate($record), $records);
    }

    private function hydrateRatingAggregate(RatingAggregateRecord $record): array
    {
        $entry = Craft::$app->getElements()->getElementById((int)$record->elementId, Entry::class, (int)$record->siteId);
        $field = Craft::$app->getFields()->getFieldById((int)$record->fieldId);
        $average = ((int)$record->voteCount > 0) ? ((float)$record->ratingSum / (int)$record->voteCount) : 0.0;

        return [
            'id' => (int)$record->id,
            'elementId' => (int)$record->elementId,
            'fieldId' => (int)$record->fieldId,
            'siteId' => (int)$record->siteId,
            'voteCount' => (int)$record->voteCount,
            'ratingSum' => (int)$record->ratingSum,
            'average' => $average,
            'roundedAverage' => round($average, 1),
            'entry' => $entry,
            'field' => $field,
            'fieldCpUrl' => UrlHelper::cpUrl('settings/fields/edit/' . (int)$record->fieldId),
            'dateCreated' => $record->dateCreated,
            'dateUpdated' => $record->dateUpdated,
        ];
    }

    private function hydrateLikesAggregate(LikesAggregateRecord $record): array
    {
        $entry = Craft::$app->getElements()->getElementById((int)$record->elementId, Entry::class, (int)$record->siteId);
        $field = Craft::$app->getFields()->getFieldById((int)$record->fieldId);
        $likeCount = (int)$record->likeCount;
        $dislikeCount = (int)$record->dislikeCount;

        return [
            'id' => (int)$record->id,
            'elementId' => (int)$record->elementId,
            'fieldId' => (int)$record->fieldId,
            'siteId' => (int)$record->siteId,
            'likeCount' => $likeCount,
            'dislikeCount' => $dislikeCount,
            'totalVotes' => $likeCount + $dislikeCount,
            'score' => $likeCount - $dislikeCount,
            'entry' => $entry,
            'field' => $field,
            'fieldCpUrl' => UrlHelper::cpUrl('settings/fields/edit/' . (int)$record->fieldId),
            'dateCreated' => $record->dateCreated,
            'dateUpdated' => $record->dateUpdated,
        ];
    }

    private function hydrateFavoritesAggregate(FavoritesAggregateRecord $record): array
    {
        $entry = Craft::$app->getElements()->getElementById((int)$record->elementId, Entry::class, (int)$record->siteId);
        $field = Craft::$app->getFields()->getFieldById((int)$record->fieldId);

        return [
            'id' => (int)$record->id,
            'elementId' => (int)$record->elementId,
            'fieldId' => (int)$record->fieldId,
            'siteId' => (int)$record->siteId,
            'favoriteCount' => (int)$record->favoriteCount,
            'entry' => $entry,
            'field' => $field,
            'fieldCpUrl' => UrlHelper::cpUrl('settings/fields/edit/' . (int)$record->fieldId),
            'dateCreated' => $record->dateCreated,
            'dateUpdated' => $record->dateUpdated,
        ];
    }

    private function applyRatingsSort(ActiveQuery $query, string $sort): void
    {
        switch ($sort) {
            case 'averageAsc':
                $query->orderBy(new Expression('CASE WHEN [[voteCount]] > 0 THEN ([[ratingSum]] * 1.0 / [[voteCount]]) ELSE 0 END ASC, [[voteCount]] ASC, [[id]] DESC'));
                return;
            case 'averageDesc':
                $query->orderBy(new Expression('CASE WHEN [[voteCount]] > 0 THEN ([[ratingSum]] * 1.0 / [[voteCount]]) ELSE 0 END DESC, [[voteCount]] DESC, [[id]] DESC'));
                return;
            case 'votesAsc':
                $query->orderBy(['voteCount' => SORT_ASC, 'id' => SORT_DESC]);
                return;
            case 'votesDesc':
                $query->orderBy(['voteCount' => SORT_DESC, 'id' => SORT_DESC]);
                return;
            case 'recent':
            default:
                $query->orderBy(['id' => SORT_DESC]);
        }
    }

    private function applyLikesSort(ActiveQuery $query, string $sort): void
    {
        switch ($sort) {
            case 'scoreAsc':
                $query->orderBy(new Expression('(CAST([[likeCount]] AS SIGNED) - CAST([[dislikeCount]] AS SIGNED)) ASC, [[id]] DESC'));
                return;
            case 'scoreDesc':
                $query->orderBy(new Expression('(CAST([[likeCount]] AS SIGNED) - CAST([[dislikeCount]] AS SIGNED)) DESC, [[id]] DESC'));
                return;
            case 'totalAsc':
                $query->orderBy(new Expression('([[likeCount]] + [[dislikeCount]]) ASC, [[id]] DESC'));
                return;
            case 'totalDesc':
                $query->orderBy(new Expression('([[likeCount]] + [[dislikeCount]]) DESC, [[id]] DESC'));
                return;
            case 'likesAsc':
                $query->orderBy(['likeCount' => SORT_ASC, 'id' => SORT_DESC]);
                return;
            case 'likesDesc':
                $query->orderBy(['likeCount' => SORT_DESC, 'id' => SORT_DESC]);
                return;
            case 'dislikesAsc':
                $query->orderBy(['dislikeCount' => SORT_ASC, 'id' => SORT_DESC]);
                return;
            case 'dislikesDesc':
                $query->orderBy(['dislikeCount' => SORT_DESC, 'id' => SORT_DESC]);
                return;
            case 'recent':
            default:
                $query->orderBy(['id' => SORT_DESC]);
        }
    }

    private function applyFavoritesSort(ActiveQuery $query, string $sort): void
    {
        switch ($sort) {
            case 'countAsc':
                $query->orderBy(['favoriteCount' => SORT_ASC, 'id' => SORT_DESC]);
                return;
            case 'countDesc':
                $query->orderBy(['favoriteCount' => SORT_DESC, 'id' => SORT_DESC]);
                return;
            case 'recent':
            default:
                $query->orderBy(['id' => SORT_DESC]);
        }
    }

    /**
     * @param array<int|null> $userIds
     * @return array<int, User>
     */
    private function usersById(array $userIds): array
    {
        $ids = array_values(array_unique(array_filter($userIds, static fn($id): bool => $id !== null)));
        if ($ids === []) {
            return [];
        }

        $users = User::find()->id($ids)->status(null)->all();
        $map = [];
        foreach ($users as $user) {
            $map[(int)$user->id] = $user;
        }

        return $map;
    }
}
