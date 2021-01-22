<?php

namespace Pushword\Core\Service;

use Intervention\Image\Image;
use Intervention\Image\ImageManager as InteventionImageManager;
use Pushword\Core\Entity\MediaInterface;
use Pushword\Core\Utils\Filepath;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class ImageManager
{
    private string $publicDir;
    private string $publicMediaDir;
    private array $filterSets;
    private OptimizerChain $optimizer;
    private ?Image $lastThumb;

    public function __construct(
        array $filterSets,
        string $publicDir,
        string $publicMediaDir
    ) {
        $this->filterSets = $filterSets;
        $this->publicDir = $publicDir;
        $this->publicMediaDir = $publicMediaDir;
        $this->optimizer = OptimizerChainFactory::create(); // todo make optimizer bin path configurable
    }

    /**
     * @param MediaInterface|string $media
     */
    public function generateCache($media): void
    {
        $image = $this->getImage($media);

        $filterNames = array_keys($this->filterSets);
        foreach ($filterNames as $filterName) {
            $lastImg = $this->generateFilteredCache($media, $filterName, $image);
            if ('thumb' == $filterName) {
                $this->lastThumb = $lastImg;
            }
        }

        exec('cd ../ && php bin/console pushword:image:optimize '.$media->getMedia().' > /dev/null 2>/dev/null &');
    }

    public function getLastThumb(): ?Image
    {
        return $this->lastThumb;
    }

    /**
     * @param MediaInterface|string $media
     */
    private function generateFilteredCache($media, string $filterName, ?Image $originalImage = null): Image
    {
        $image = null === $originalImage ? $this->getImage($media)
            : ('default' == $filterName ? $originalImage : clone $originalImage); // don't clone if default for speed perf

        foreach ($this->filterSets[$filterName]['filters'] as $filter => $parameters) {
            $parameters = \is_array($parameters) ? $parameters : [$parameters];
            $this->normalizeFilter($filter, $parameters);
            \call_user_func_array([$image, $filter], $parameters);
        }

        $quality = $this->filterSets[$filterName]['quality'] ?? 90;

        $image->save($this->getFilterPath($media, $filterName), $quality);
        $image->save($this->getFilterPath($media, $filterName, 'webp'), $quality, 'webp');

        $this->getFilterPath($media, $filterName);

        return $image;
    }

    public function optimize(MediaInterface $media)
    {
        $filterNames = array_keys($this->filterSets);
        foreach ($filterNames as $filterName) {
            $this->optimizeFiltered($media, $filterName);
        }
    }

    private function optimizeFiltered(MediaInterface $media, string $filterName)
    {
        if (! file_exists($this->getFilterPath($media, $filterName)) || ! file_exists($this->getFilterPath($media, $filterName, 'webp'))) {
            $this->generateFilteredCache($media, $filterName);
        }

        $this->optimizer->optimize($this->getFilterPath($media, $filterName));
        $this->optimizer->optimize($this->getFilterPath($media, $filterName, 'webp'));
    }

    /**
     * Transform {$fiter}_notupsize in $fiter and add constrait->upsize()
     * or transform dowscale in resize with aspectRatio and upSize contraint.
     */
    private function normalizeFilter(string &$filter, array &$parameters): void
    {
        if ('downscale' == $filter) {
            $parameters[] = function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            };
            $filter = 'resize';
        }

        if (str_ends_with($filter, '_notupsize')) {
            $parameters[] = function ($constraint) {
                $constraint->upsize();
            };
            $filter = str_replace('_notupsize', '', $filter);
        }

        if (isset($parameters['constraint']) && \is_string($parameters['constraint'])) {
            $parameters[] = eval("return function(\$constraint) {{$parameters['constraint']}};");
            unset($parameters['constraint']);
        }
    }

    /**
     * @param MediaInterface|string $media
     */
    public function getFilterPath($media, string $filterName = null, ?string $extension = null, $browserPath = false): string
    {
        /** @var string $media */
        $media = $media instanceof MediaInterface ? $media->getMedia() : $media;

        $fileName = null === $extension ? $media : Filepath::removeExtension($media).'.'.$extension;

        return ($browserPath ? '' : $this->publicDir).$this->publicMediaDir.'/'.$filterName.'/'.$fileName;
    }

    /**
     * @param MediaInterface|string $media
     */
    public function getBrowserPath($media, string $filterName = 'default', ?string $extension = null): string
    {
        return $this->getFilterPath($media, $filterName, $extension, true);
    }

    /**
     * @param MediaInterface|string $media string must be the accessible path (absolute) to the image file
     */
    private function getImage($media): Image
    {
        $path = $media instanceof MediaInterface ? $media->getPath() : $media;

        return (new InteventionImageManager())->make($path); // default driver GD
    }

    /**
     * @param MediaInterface|string $media
     */
    public function remove($media): void
    {
        if ($media instanceof MediaInterface) {
            $media = $media->getMedia();
        }

        $filterNames = array_keys($this->filterSets);
        foreach ($filterNames as $filterName) {
            @unlink($this->publicDir.$this->publicMediaDir.'/'.$filterName.'/'.$media);
        }
    }
}
