<?php

namespace Gitiki\CodeHighlight\Controller;

use Silex\Application;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException,
    Symfony\Component\HttpFoundation\File\File;

class AssetsController
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function libraryAction()
    {
        return $this->sendFile('highlight.js');
    }

    public function languageAction($language, $_format)
    {
        return $this->sendFile('languages/'.$language.'.'.$_format, 'Gitiki\CodeHighlight\HttpFoundation\HighlightLanguageResponse');
    }

    public function styleAction($style, $_format)
    {
        return $this->sendFile('styles/'.$style.'.'.$_format);
    }

    protected function sendFile($file, $responseClass = null)
    {
        try {
            $fileInfo = new File(__DIR__.'/../Resources/highlightjs/'.$file);
        } catch (FileNotFoundException $e) {
            $this->app->abort(404, 'The file "%s" does not exists');
        }

        if (!$responseClass) {
            $response = $this->app->sendFile($fileInfo);
        } else {
            $response = new $responseClass($fileInfo);
        }

        $request = $this->app['request'];
        $response->headers->set('content-type', $request->getMimeType($fileInfo->getExtension()));

        $response
            ->setMaxAge(0)
            ->isNotModified($request)
        ;

        return $response;
    }
}
