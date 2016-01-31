<?php

namespace Gitiki\CodeHighlight\Event\Listener;

use Gitiki\Event\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface,
    Symfony\Component\EventDispatcher\GenericEvent as Event,
    Symfony\Component\HttpKernel\Event\FilterResponseEvent,
    Symfony\Component\HttpKernel\Event\GetResponseEvent,
    Symfony\Component\HttpKernel\KernelEvents,
    Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CodeHighLightListener implements EventSubscriberInterface
{
    protected $urlGenerator;

    protected $languages;

    protected $basePath;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;

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

    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->basePath = $event->getRequest()->getBasePath();
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
                $this->languages[$language] = sprintf('<script src="%s"></script>', $this->urlGenerator->generate('_highlight_language', [
                    'language' => $language,
                    '_format' => 'js',
                ]));
            }

            $content = sprintf('%s  <script src="%s/assets/highlightjs.js"></script>%s<script>hljs.initHighlightingOnLoad();</script>'."\n".'  %s', substr($content, 0, $bodyPos), $this->basePath, implode($this->languages), substr($content, $bodyPos));

            $content = sprintf('%s  <link href="%s/assets/highlightjs.css" rel="stylesheet">'."\n".'  %s', substr($content, 0, $headPos), $this->basePath, substr($content, $headPos));

        }

        $response->setContent($content);
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::PAGE_CONTENT => ['onContent', 512],
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
