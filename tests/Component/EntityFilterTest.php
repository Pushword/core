<?php

namespace Pushword\Core\Tests\Component;

use Pushword\Core\Component\EntityFilter\Filter\HtmlEncryptedLink;
use Pushword\Core\Component\EntityFilter\ManagerPool;
use Pushword\Core\Entity\Page;
use Pushword\Core\Router\PushwordRouteGenerator;
use Pushword\Core\Service\LinkProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EntityFilterTest extends KernelTestCase
{
    public function testIt()
    {
        $manager = $this->getManagerPool()->getManager($this->getPage());

        $this->assertSame($this->getPage()->getH1(), $manager->title());
        $this->assertSame($this->getPage()->getH1(), $manager->getTitle());
        $this->assertSame('', $manager->getMainContent()->getChapeau());
        $this->assertSame('<p>', substr(trim($manager->getMainContent()->getBody()), 0, 3));
    }

    public function testEncryptedLink()
    {
        self::bootKernel();

        $filter = new HtmlEncryptedLink();
        $filter->app = ($apps = self::$kernel->getContainer()->get(\Pushword\Core\Component\App\AppPool::class))->getApp();
        $filter->twig = self::$kernel->getContainer()->get('test.service_container')->get('twig');
        $router = self::$kernel->getContainer()->get(PushwordRouteGenerator::class);
        $filter->linkProvider = new LinkProvider($router, $apps, $filter->twig);
        $this->assertSame(
            'Lorem <span data-rot=_cvrqjro.pbz/>Test</span> ipsum',
            $filter->convertHtmlRelEncryptedLink('Lorem <a href="https://piedweb.com/" rel="encrypt">Test</a> ipsum')
        );
        $this->assertSame(
            'Lorem <span class=link-btn data-rot=_cvrqjro.pbz/>Test</span> ipsum',
            $filter->convertHtmlRelEncryptedLink('Lorem <a class="link-btn" href="https://piedweb.com/" rel="encrypt">Test</a> ipsum')
        );
        $this->assertSame(
            'Lorem <span class="link-btn btn-plus" data-rot=_cvrqjro.pbz/>Test</span> ipsum',
            $filter->convertHtmlRelEncryptedLink('Lorem <a class="link-btn btn-plus" href="https://piedweb.com/" rel="encrypt">Test</a> ipsum')
        );
        $this->assertSame(
            'Lorem <span class="link-btn btn-plus" data-rot=&>Test</span> ipsum',
            $filter->convertHtmlRelEncryptedLink('Lorem <a class="link-btn btn-plus" href="&" rel="encrypt">Test</a> ipsum')
        );

        $this->assertSame(
            'Lorem <a href="/a1" class="ninja">Test</a> <span data-rot=_cvrqjro.pbz/>Anchor 2</span>',
            $filter->convertHtmlRelEncryptedLink('Lorem <a href="/a1" class="ninja">Test</a> <a href="https://piedweb.com/" rel="encrypt">Anchor 2</a>')
        );
    }

    private function getManagerPool()
    {
        self::bootKernel();

        return new ManagerPool(
            $apps = self::$kernel->getContainer()->get(\Pushword\Core\Component\App\AppPool::class),
            $twig = self::$kernel->getContainer()->get('test.service_container')->get('twig'),
            self::$kernel->getContainer()->get('event_dispatcher'),
            $router = self::$kernel->getContainer()->get(PushwordRouteGenerator::class),
            new LinkProvider($router, $apps, $twig),
            self::$kernel->getContainer()->get('doctrine.orm.default_entity_manager')
        );
    }

    public function testToc()
    {
        $page = $this->getPage($this->getContentReadyForToc());

        /** @var \Pushword\Core\Component\EntityFilter\Manager */
        $manager = $this->getManagerPool()->getManager($page);

        $this->assertSame('<p>my intro...</p>', trim($manager->getMainContent()->getIntro()));
        $toCheck = '<h2 id="first-title">First Title</h2>';
        $this->assertSame($toCheck, substr(trim($manager->getMainContent()->getContent()), 0, \strlen($toCheck)));
    }

    private function getPage($content = null)
    {
        $page = (new Page())
            ->setH1('Demo Page - Kitchen Sink  Markdown + Twig')
            ->setSlug('kitchen-sink')
            ->setLocale('en')
            ->setCreatedAt(new \DateTime('1 day ago'))
            ->setUpdatedAt(new \DateTime('1 day ago'))
            ->setMainContent($content ?? file_get_contents(__DIR__.'/../../../skeleton/src/DataFixtures/WelcomePage.md'));
        $page->setCustomProperty('toc', true);

        return $page;
    }

    private function getContentReadyForToc()
    {
        return 'my intro...'
            .\chr(10).'## First Title'
            .\chr(10).'first paragraph'
            .\chr(10).'## Second Title'
            .\chr(10).'second paragraph';
    }
}
