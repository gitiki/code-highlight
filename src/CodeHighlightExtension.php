<?php

namespace Gitiki\CodeHighlight;

use Gitiki\ExtensionInterface,
    Gitiki\Gitiki;

use Symfony\Component\Config\Definition\Builder\TreeBuilder,
    Symfony\Component\Config\Definition\Processor;

class CodeHighlightExtension implements ExtensionInterface
{
    public function register(Gitiki $gitiki, array $config)
    {
        $gitiki['code_highlight'] = $this->registerConfiguration($gitiki, $config);

        $gitiki['dispatcher'] = $gitiki->share($gitiki->extend('dispatcher', function ($dispatcher, $gitiki) {
            $dispatcher->addSubscriber(new Event\Listener\CodeHighlightListener($gitiki['url_generator'], $gitiki['code_highlight']['style']));

            return $dispatcher;
        }));

        $gitiki['code_highlight.controller.assets'] = $gitiki->share(function() use ($gitiki) {
            return new Controller\AssetsController($gitiki);
        });

        $this->registerRouting($gitiki);
    }

    public function boot(Gitiki $gitiki)
    {
    }

    protected function registerConfiguration(Gitiki $gitiki, array $config)
    {
        $treeBuilder = new TreeBuilder();

        $treeBuilder->root('code_highlight')
            ->children()
                ->scalarNode('style')->cannotBeEmpty()->defaultValue('tomorrow')->end()
            ->end()
        ;

        return (new Processor())->process($treeBuilder->buildTree(), [$config]);
    }

    protected function registerRouting(Gitiki $gitiki)
    {
        $gitiki->get('/highlight.{_format}', 'code_highlight.controller.assets:libraryAction')
            ->assert('_format', 'js')
            ->bind('_highlight_library');

        $gitiki->get('/languages/{language}.{_format}', 'code_highlight.controller.assets:languageAction')
            ->assert('_format', 'js')
            ->bind('_highlight_language');

        $gitiki->get('/styles/{style}.{_format}', 'code_highlight.controller.assets:styleAction')
            ->assert('_format', 'css')
            ->bind('_highlight_style');

        $gitiki->flush('_highlight');
    }
}
