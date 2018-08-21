<?php

/**
 * Craft Entry relations manager plugin
 *
 * @author    Frontwise
 * @copyright Copyright (c) 2018 Frontwise
 * @link      https://frontwise.com
 */

namespace frontwise\entryrelationsmanager;

use Craft;

use craft\base\Plugin;

use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use craft\web\View;
use craft\web\twig\variables\Cp;
use yii\base\Event;

class EntryRelationsManagerPlugin extends Plugin
{
    public function init()
    {
        parent::init();

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, [$this, 'registerCpUrlRules']);

        Craft::info('frontwise/EntryRelationsManager plugin loaded', __METHOD__);
    }

    public function registerCpUrlRules(RegisterUrlRulesEvent $event)
    {
        $rules = [
            'entry-relations-manager' => 'entry-relations-manager/fields/index',
        ];

        $event->rules = array_merge($event->rules, $rules);
    }
}