<?php

namespace jtdev\craftengagement;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\elements\db\ElementQuery;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use craft\web\twig\variables\CraftVariable;
use jtdev\craftengagement\fields\FavoritesField;
use jtdev\craftengagement\fields\LikesField;
use jtdev\craftengagement\fields\RatingField;
use jtdev\craftengagement\helpers\EngagementQueryHelper;
use jtdev\craftengagement\models\Settings;
use jtdev\craftengagement\services\FavoritesAggregateService;
use jtdev\craftengagement\services\FavoritesEntryService;
use jtdev\craftengagement\services\LikesAggregateService;
use jtdev\craftengagement\services\LikesVoteService;
use jtdev\craftengagement\services\RatingAggregateService;
use jtdev\craftengagement\services\RatingVoteService;
use jtdev\craftengagement\services\TwigService;
use jtdev\craftengagement\variables\EngagementVariable;
use yii\base\Event;

/**
 * Engagement plugin
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 * @property-read RatingAggregateService $ratingAggregates
 * @property-read RatingAggregateService $aggregates
 * @property-read TwigService $twig
 * @property-read RatingVoteService $ratingVotes
 * @property-read RatingVoteService $votes
 * @property-read LikesAggregateService $likesAggregates
 * @property-read LikesVoteService $likesVotes
 * @property-read LikesVoteService $likes
 * @property-read FavoritesAggregateService $favoritesAggregates
 * @property-read FavoritesEntryService $favoritesEntries
 * @property-read FavoritesEntryService $favorites
 * @author JTDev <jake.trapp02@gmail.com>
 * @copyright JTDev
 * @license https://craftcms.github.io/license/ Craft License
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                'ratingAggregates' => RatingAggregateService::class,
                'aggregates' => RatingAggregateService::class,
                'twig' => TwigService::class,
                'ratingVotes' => RatingVoteService::class,
                'votes' => RatingVoteService::class,
                'likesAggregates' => LikesAggregateService::class,
                'likesVotes' => LikesVoteService::class,
                'likes' => LikesVoteService::class,
                'favoritesAggregates' => FavoritesAggregateService::class,
                'favoritesEntries' => FavoritesEntryService::class,
                'favorites' => FavoritesEntryService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'jtdev\\craftengagement\\console\\controllers';
        }

        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
            // ...
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('engagement/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            static function(RegisterComponentTypesEvent $event): void {
                $event->types[] = RatingField::class;
                $event->types[] = LikesField::class;
                $event->types[] = FavoritesField::class;
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            static function(Event $event): void {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('engagement', EngagementVariable::class);
            }
        );

        Event::on(
            ElementQuery::class,
            ElementQuery::EVENT_BEFORE_PREPARE,
            static function(Event $event): void {
                /** @var ElementQuery $query */
                $query = $event->sender;
                EngagementQueryHelper::rewriteOrderBy($query);
            }
        );
    }
}
