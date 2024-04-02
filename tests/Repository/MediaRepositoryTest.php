<?php

namespace Pushword\Core\Tests\Controller;

use Pushword\Core\Entity\Media;
use Pushword\Core\Repository\MediaRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MediaRepositoryTest extends KernelTestCase
{
    public function testFindDuplicate(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get('doctrine.orm.default_entity_manager');

        /** @var MediaRepository */
        $mediaRepo = $em->getRepository(Media::class);

        $duplicate = $mediaRepo->findDuplicate((new Media())->setHash('testFakeHash'));
        self::assertNull($duplicate);

        $duplicate = $em->getRepository(Media::class)->findDuplicate($this->getMediaToTestDuplicate());
        self::assertInstanceOf(Media::class, $duplicate);
    }

    public function getMediaToTestDuplicate(): Media
    {
        return (new Media())->setProjectDir(self::getContainer()->getParameter('kernel.project_dir'))
            ->setStoreIn(self::getContainer()->getParameter('pw.media_dir'))
            ->setMimeType('image/jpg')
            ->setSize(2)
            ->setDimensions([1000, 1000])
            ->setMedia('1.jpg')
            ->setName('Demo 1')
            ->setHash();
    }
}
