<?php

namespace Gitiki\CodeHighlight;

use Gitiki\Extension\WebpackInterface,
    Gitiki\ExtensionInterface,
    Gitiki\Gitiki;

use Symfony\Component\Config\Definition\Builder\TreeBuilder,
    Symfony\Component\Config\Definition\Processor;

class CodeHighlightExtension implements ExtensionInterface, WebpackInterface
{
    public function register(Gitiki $gitiki, array $config)
    {
        $gitiki['code_highlight'] = $this->registerConfiguration($gitiki, $config);

        $gitiki['dispatcher'] = $gitiki->share($gitiki->extend('dispatcher', function ($dispatcher, $gitiki) {
            $dispatcher->addSubscriber(new Event\Listener\CodeHighlightListener($gitiki['url_generator']));

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

    public function getWebpackEntries(Gitiki $gitiki)
    {
        return [
            'highlightjs' => [
                'expose?hljs!'.__DIR__.'/Resources/highlightjs/highlight.js',
                __DIR__.'/Resources/highlightjs/styles/'.$gitiki['code_highlight']['style'].'.css',
            ]
        ];
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
        $gitiki->get('/languages/{language}.{_format}', 'code_highlight.controller.assets:languageAction')
            ->assert('_format', 'js')
            ->bind('_highlight_language');

        $gitiki->flush('_highlight');
    }
}
