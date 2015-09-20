<?php

namespace Gitiki\CodeHighlight;

use Gitiki\ExtensionInterface;

use Silex\Application;

class CodeHighlightExtension implements ExtensionInterface
{
    public static function getConfigurationKey()
    {
        return 'code_highlight';
    }

    public function register(Application $app)
    {
        $configurationKey = static::getConfigurationKey();
        $app[$configurationKey] = [
            'style' => 'tomorrow',
        ];

        $app['dispatcher'] = $app->share($app->extend('dispatcher', function ($dispatcher, $app) use ($configurationKey) {
            $dispatcher->addSubscriber(new Event\Listener\CodeHighlightListener($app['url_generator'], $app[$configurationKey]['style']));

            return $dispatcher;
        }));

        $app['code_highlight.controller.assets'] = $app->share(function() use ($app) {
            return new Controller\AssetsController($app);
        });

        $this->registerRouting($app);
    }

    public function boot(Application $app)
    {
    }

    protected function registerRouting(Application $app)
    {
        $app->get('/_highlight/highlight.{_format}', 'code_highlight.controller.assets:libraryAction')
            ->assert('_format', 'js')
            ->bind('_highlight_library');

        $app->get('/_highlight/languages/{language}.{_format}', 'code_highlight.controller.assets:languageAction')
            ->assert('_format', 'js')
            ->bind('_highlight_language');

        $app->get('/_highlight/styles/{style}.{_format}', 'code_highlight.controller.assets:styleAction')
            ->assert('_format', 'css')
            ->bind('_highlight_style');
    }
}
