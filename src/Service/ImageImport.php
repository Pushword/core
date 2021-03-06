<?php

namespace Pushword\Core\Service;

use Cocur\Slugify\Slugify;
use Exception;
use Pushword\Core\Entity\Media;
use Pushword\Core\Entity\MediaInterface;

trait ImageImport
{
    private function generateFileName(string $url, string $mimeType, string $slug, bool $hashInFilename): string
    {
        $slug = (new Slugify())->slugify($slug);

        return ($slug ?: pathinfo($url, \PATHINFO_BASENAME))
            .($hashInFilename ? '-'.substr(md5(sha1($url)), 0, 4) : '')
            .'.'.str_replace(['image/', 'jpeg'], ['', 'jpg'], $mimeType);
    }

    public function importExternal(string $image, string $name = '', string $slug = '', $hashInFilename = true): MediaInterface
    {
        $imageLocalImport = $this->cacheExternalImage($image);

        $imgSize = getimagesize($imageLocalImport);
        if (false === $imgSize) {
            throw new Exception('Image `'.$image.'` was not imported.');
        }

        $fileName = $this->generateFileName($image, $imgSize['mime'], $slug ?: $name, $hashInFilename);

        $newFilePath = $this->mediaDir.'/'.$fileName;

        $media = new Media();
        $media
                ->setStoreIn($this->mediaDir)
                ->setMimeType($imgSize['mime'])
                ->setSize(filesize($imageLocalImport))
                ->setDimensions([$imgSize[0], $imgSize[1]])
                ->setMedia($fileName)
                ->setName(str_replace(["\n", '"'], ' ', $name));

        if (! file_exists($newFilePath)) {
            $this->fileSystem->copy($imageLocalImport, $newFilePath);

            $this->generateCache($media);
        }

        return $media;
    }

    /**
     * Undocumented function.
     *
     * @return false|string
     */
    public function cacheExternalImage(string $src)
    {
        $filePath = sys_get_temp_dir().'/'.sha1($src);
        if (file_exists($filePath)) {
            return $filePath;
        }

        if (\function_exists('curl_init')) {
            $curl = curl_init($src);
            curl_setopt($curl, \CURLOPT_RETURNTRANSFER, 1);
            /** @var false|string $content */
            $content = curl_exec($curl);
            curl_close($curl);
        } else {
            $content = file_get_contents($src);
        }

        if (false === $content || false === imagecreatefromstring($content)) {
            return false;
        }

        $filePath = sys_get_temp_dir().'/'.sha1($src);
        if (false === file_put_contents($filePath, $content)) {
            throw new Exception('An error occured caching external resource in system tmp dir.');
        }

        return $filePath;
    }
}
