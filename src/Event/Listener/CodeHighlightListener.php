<?php

namespace Gitiki\CodeHighlight\Event\Listener;

use Gitiki\Event\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface,
    Symfony\Component\EventDispatcher\GenericEvent as Event,
    Symfony\Component\HttpKernel\Event\FilterResponseEvent,
    Symfony\Component\HttpKernel\KernelEvents,
    Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CodeHighLightListener implements EventSubscriberInterface
{
    protected $urlGenerator;

    protected $style;

    protected $languages;

    public function __construct(UrlGeneratorInterface $urlGenerator, $style)
    {
        $this->urlGenerator = $urlGenerator;

        $this->style = $style;

        $this->languages = [];
    }

    public function onContent(Event $event)
    {
        $page = $event->getSubject();

        $noHighlight = [];
        foreach ($page->getDocument()->getElementsByTagName('code') as $code) {
            if (!$code->hasAttribute('class')) {
                $noHighlight[] = $code;

                continue;
            } elseif ('language-nohighlight' === $code->getAttribute('class')) {
                continue;
            }

            $this->languages[substr(strstr($code->getAttribute('class'), '-'), 1)] = null;
        }

        if (!empty($this->languages)) {
            foreach ($noHighlight as $code) {
                $code->setAttribute('class', 'nohighlight');
            }
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        } elseif (empty($this->languages)) {
            return;
        }

        $response = $event->getResponse();
        $content = $response->getContent();

        $headPos = strripos($content, '</head>'); // search head for css
        $bodyPos = strripos($content, '</body>'); // search body for js

        // add css and js
        if (false !== $headPos && false !== $bodyPos) {
            foreach ($this->languages as $language => $v) {
                $this->languages[$language] = '<script src="'.$this->urlGenerator->generate('_highlight_language', [
                    'language' => $language,
                    '_format' => 'js',
                ]).'"></script>';
            }

            $content = substr($content, 0, $bodyPos).'  <script src="'.$this->urlGenerator->generate('_highlight_library', [
                '_format' => 'js',
            ]).'"></script>'.implode($this->languages).'<script>hljs.initHighlightingOnLoad();</script>'."\n  ".substr($content, $bodyPos);

            $content = substr($content, 0, $headPos).'  <link href="'.$this->urlGenerator->generate('_highlight_style', [
                'style' => $this->style,
                '_format' => 'css',
            ]).'" rel="stylesheet">'."\n  ".substr($content, $headPos);
        }

        $response->setContent($content);
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::PAGE_CONTENT => ['onContent', 512],
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
