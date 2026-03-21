<?php

namespace jtdev\craftengagement;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use craft\web\twig\variables\CraftVariable;
use jtdev\craftengagement\fields\RatingField;
use jtdev\craftengagement\models\Settings;
use jtdev\craftengagement\services\AggregateService;
use jtdev\craftengagement\services\TwigService;
use jtdev\craftengagement\services\VoteService;
use jtdev\craftengagement\variables\EngagementVariable;
use yii\base\Event;

/**
 * Engagement plugin
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 * @property-read AggregateService $aggregates
 * @property-read TwigService $twig
 * @property-read VoteService $votes
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
                'aggregates' => AggregateService::class,
                'twig' => TwigService::class,
                'votes' => VoteService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

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
    }
}
