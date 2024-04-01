<?php

namespace Pushword\Core\Tests\Controller;

use Pushword\Core\Entity\Page;
use Pushword\Core\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PageRepositoryTest extends KernelTestCase
{
    public function testPageRepo()
    {
        self::bootKernel();

        $em = self::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        /** @var PageRepository */
        $pageRepo = $em->getRepository(Page::class);
        $this->assertInstanceOf(PageRepository::class, $pageRepo);

        $pages = $pageRepo->getIndexablePagesQuery('', 'en', 2)
            ->getQuery()->getResult();

        $this->assertSame(2, \count($pages)); // depend on AppFixtures

        $pages = $pageRepo->getPublishedPages(
            '',
            [['key' => 'slug', 'operator' => '=', 'value' => 'homepage']],
            ['key' => 'publishedAt', 'direction' => 'DESC'],
            1
        );

        $this->assertSame($pages[0]->getSlug(), 'homepage');
    }
}
