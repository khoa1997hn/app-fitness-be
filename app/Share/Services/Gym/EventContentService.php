<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\FileType;
use App\Share\Services\File\FileUploadService;
use DOMDocument;
use DOMElement;
use HTMLPurifier;
use HTMLPurifier_Config;

class EventContentService
{
    public function imagePathPattern(): string
    {
        $prefix = preg_quote(config('app_file.'.FileType::EventImage.'.prefix_path'), '~');

        return '~^'.$prefix.'/[a-zA-Z0-9]{40}\.(?:webp|jpg|jpeg|png)$~D';
    }

    public function sanitize(?string $content): ?string
    {
        if ($content === null || ! preg_match('/<\/?[a-z][\s\S]*>/i', $content)) {
            return $content;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p,br,h2,h3,strong,em,u,s,ul,ol,li,blockquote,a[href|title],img[src|alt|title|data-file-path],hr');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->getHTMLDefinition(true)->addAttribute('img', 'data-file-path', 'Text');
        $clean = (new HTMLPurifier($config))->purify($content);

        return $this->transformImages($clean, false);
    }

    public function forDisplay(?string $content): ?string
    {
        return $this->transformImages($this->sanitize($content), true);
    }

    private function transformImages(?string $content, bool $display): ?string
    {
        if ($content === null || ! str_contains($content, '<img')) {
            return $content;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML('<?xml encoding="UTF-8"><div>'.$content.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $images = iterator_to_array($document->getElementsByTagName('img'));

        foreach ($images as $image) {
            /** @var DOMElement $image */
            $path = $image->getAttribute('data-file-path') ?: $image->getAttribute('src');

            if (! preg_match($this->imagePathPattern(), $path)) {
                $image->parentNode->removeChild($image);

                continue;
            }

            $image->removeAttribute('data-file-path');
            $image->setAttribute('src', $display ? app(FileUploadService::class)->getUrl($path) : $path);

            if ($display) {
                $image->setAttribute('data-file-path', $path);
                $image->setAttribute('loading', 'lazy');
                $image->setAttribute('decoding', 'async');
            }
        }

        $root = $document->getElementsByTagName('div')->item(0);
        $html = '';
        foreach ($root->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }

        return $html;
    }
}
