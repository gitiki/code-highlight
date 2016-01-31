<?php

namespace Gitiki\CodeHighlight\Controller;

use Gitiki\Gitiki;

use Silex\Application;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException,
    Symfony\Component\HttpFoundation\File\File;

class AssetsController
{
    public function languageAction(Gitiki $gitiki, $language, $_format)
    {
        return $this->sendFile($gitiki, 'languages/'.$language.'.'.$_format, 'Gitiki\CodeHighlight\HttpFoundation\HighlightLanguageResponse');
    }

    protected function sendFile(Gitiki $gitiki, $file, $responseClass)
    {
        try {
            $fileInfo = new File(__DIR__.'/../Resources/highlightjs/'.$file);
        } catch (FileNotFoundException $e) {
            $gitiki->abort(404, 'The file "%s" does not exists');
        }

        $response = new $responseClass($fileInfo);

        $request = $gitiki['request'];
        $response->headers->set('content-type', $request->getMimeType($fileInfo->getExtension()));

        $response
            ->setMaxAge(0)
            ->isNotModified($request)
        ;

        return $response;
    }
}
