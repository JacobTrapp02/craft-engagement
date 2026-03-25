# Engagement

User engagment -- customizable ratings, likes, and favorites

## Requirements

This plugin requires Craft CMS 5.0.0 or later, and PHP 8.2 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Engagement”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require jt-dev/craft-engagement

# tell Craft to install the plugin
./craft plugin/install engagement
```

## Widget Template Overrides

Each widget now resolves three template handles from `config/engagement.php`:

- `html`
- `css`
- `js`

Defaults are:

- `favorites`: `engagement/favorites/_render/widgethtml`, `engagement/favorites/_render/widgetcss`, `engagement/favorites/_render/widgetjs`
- `likes`: `engagement/likes/_render/widgethtml`, `engagement/likes/_render/widgetcss`, `engagement/likes/_render/widgetjs`
- `ratings`: `engagement/ratings/_render/widgethtml`, `engagement/ratings/_render/widgetcss`, `engagement/ratings/_render/widgetjs`

To override, create `config/engagement.php` in your Craft project and point to your own site template handles, for example:

```php
<?php

return [
    'widgetTemplates' => [
        'favorites' => [
            'html' => '_engagement/favorites/widgethtml',
            'css' => '_engagement/favorites/widgetcss',
            'js' => '_engagement/favorites/widgetjs',
        ],
    ],
];
```

You can also override templates per render call (optional 2nd arg):

```twig
{{ craft.engagement.render(entry.myRatingField) }}

{{ craft.engagement.render(entry.myRatingField, {
  htmlTemplate: '_engagement/ratings/widgethtml'
}) }}

{{ craft.engagement.render(entry.myRatingField, {
  htmlTemplate: '_engagement/ratings/widgethtml',
  cssTemplate: '_engagement/ratings/widgetcss',
  jsTemplate: '_engagement/ratings/widgetjs',
}) }}
```

## Plugin Settings

In plugin settings, you can configure authentication links used by widgets:

- `Login URL`
- `Login Redirect Param` (optional, e.g. `redirect`)
- `Register URL` (optional)

If a register URL is set, guest prompts can offer registration as an alternative to login.

## Querying Entries by Engagement Field

Engagement fields support Craft-native field-handle querying directly on element queries:

```twig
{% set entries = craft.entries()
  .section('reviews')
  .rating({
    enabled: true,
    minAverage: 4,
    minVoteCount: 10
  })
  .all() %}
```

This uses your actual field handle (`rating`, `likes`, `favorites`, etc.) and works alongside normal Craft query methods (`section()`, `site()`, `orderBy()`, `limit()`, etc.).

### Field Value Access (Unchanged)

These read values still work the same in Twig:

- `entry.rating.average`
- `entry.rating.voteCount`
- `entry.likes.likeCount`
- `entry.likes.dislikeCount`
- `entry.likes.totalVotes` (derived: `likeCount + dislikeCount`)
- `entry.likes.score` (derived: `likeCount - dislikeCount`)
- `entry.favorites.favoriteCount`

### Shared Criteria Keys

All three engagement field types support:

- `enabled`: Effective enabled state for that entry’s field value.
  - Uses entry override when widget-enabled override is allowed.
  - Falls back to field default enabled state otherwise.
- `siteId`: Filter against a specific site’s aggregate values.

### Rating Criteria

Use these keys inside `.<ratingHandle>({...})`:

- `minAverage`, `maxAverage`
- `minVoteCount`, `maxVoteCount`
- `hasVotes` (`true` or `false`)

Example:

```twig
{% set topRated = craft.entries()
  .section('reviews')
  .rating({
    enabled: true,
    minAverage: 4.2,
    minVoteCount: 25,
    hasVotes: true
  })
  .all() %}
```

### Likes Criteria

Use these keys inside `.<likesHandle>({...})`:

- `minLikes`, `maxLikes`
- `minDislikes`, `maxDislikes`
- `minTotalVotes`, `maxTotalVotes`
- `minScore`, `maxScore` (`score = likeCount - dislikeCount`)

`totalVotes` is a derived metric, not a physical database column. It is computed from `likeCount + dislikeCount`.

Example:

```twig
{% set trending = craft.entries()
  .section('news')
  .likes({
    enabled: true,
    minTotalVotes: 15,
    minScore: 5
  })
  .all() %}
```

### Favorites Criteria

Use these keys inside `.<favoritesHandle>({...})`:

- `minFavorites`, `maxFavorites`
- `hasFavorites` (`true` or `false`)

Example:

```twig
{% set bookmarked = craft.entries()
  .section('articles')
  .favorites({
    enabled: true,
    minFavorites: 10
  })
  .all() %}
```

### No-Aggregate Defaults

If an entry has no aggregate row yet:

- Counts are treated as `0`
- Ratings average is treated as `0`

This keeps filtering behavior predictable for new entries with no interactions.

### Sorting by Engagement Metrics

Use native Craft `orderBy(...)` with handle-aware metric keys:

- Ratings:
  - `<handle>__average`
  - `<handle>__voteCount`
- Likes:
  - `<handle>__likes`
  - `<handle>__dislikes`
  - `<handle>__score`
  - `<handle>__totalVotes`
- Favorites:
  - `<handle>__count`

Examples:

```twig
{% set byRating = craft.entries()
  .section('reviews')
  .rating({ minVoteCount: 5 })
  .orderBy('rating__average desc')
  .all() %}

{% set byLikeScore = craft.entries()
  .section('news')
  .likes({ minTotalVotes: 10 })
  .orderBy('likes__score desc')
  .all() %}

{% set byFavorites = craft.entries()
  .section('articles')
  .favorites({ hasFavorites: true })
  .orderBy('favorites__count desc')
  .all() %}
```

The handle prefix makes sorting unambiguous if multiple engagement fields exist.

### Validation Behavior

Criteria parsing is intentionally forgiving:

- Unknown keys are ignored
- Invalid values are ignored for that key
- If a min is greater than its max (for the same metric), the query returns no results

## Recount Commands

You can force recount and recovery for a single aggregate row by ID:

```bash
php craft engagement/recount/rating 123
php craft engagement/recount/like 123
php craft engagement/recount/favorite 123
```

You can also recount all aggregates for a type:

```bash
php craft engagement/recount/rating --all
php craft engagement/recount/like --all
php craft engagement/recount/favorite --all
```

Plural aliases are also available:

```bash
php craft engagement/recount/ratings 123
php craft engagement/recount/likes 123
php craft engagement/recount/favorites 123
```
