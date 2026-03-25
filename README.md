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
