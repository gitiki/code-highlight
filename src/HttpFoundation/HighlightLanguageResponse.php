<?php

namespace Gitiki\CodeHighlight\HttpFoundation;

use Symfony\Component\HttpFoundation\BinaryFileResponse,
    Symfony\Component\HttpFoundation\Request;

class HighlightLanguageResponse extends BinaryFileResponse
{
    protected $prepend;
    protected $append;

    public function prepare(Request $request)
    {
        parent::prepare($request);

        $this->headers->set('content-length', $this->headers->get('content-length') + strlen($this->prepend) + strlen($this->append));

        return $this;
    }

    public function setFile($file, $contentDisposition = null, $autoEtag = false, $autoLastModified = true)
    {
        parent::setFile($file, $contentDisposition, $autoEtag, $autoLastModified);

        $this->prepend = 'hljs.registerLanguage("'.$this->file->getBasename('.'.$this->file->getExtension()).'", ';
        $this->append = ');';

        return $this;
    }

    public function sendContent()
    {
        echo $this->prepend;

        parent::sendContent();

        echo $this->append;
    }
}
