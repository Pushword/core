<?php

namespace Pushword\Core\Twig;

use Pushword\Core\Component\App\AppConfig;

trait GalleryTwigTrait
{
    abstract public function getApp(): AppConfig;

    public function renderGallery(array $images)
    {
        $template = $this->getApp()->getView('/component/images_gallery.html.twig');

        return $this->twig->render($template, [
            'images' => $images,
            //'grid_cols' => $gridCols
        ]);
    }
}
