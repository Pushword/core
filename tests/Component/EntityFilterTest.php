<?php

namespace Pushword\Core\Tests\Component;

use DateTime;
use Pushword\Core\Component\EntityFilter\Filter\HtmlEncryptedLink;
use Pushword\Core\Component\EntityFilter\ManagerPool;
use Pushword\Core\Entity\Page;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EntityFilterTest extends KernelTestCase
{
    public function testIt()
    {
        $manager = $this->getManagerPool()->getManager($this->getPage());

        $this->assertSame($this->getPage()->getH1(), $manager->title());
        $this->assertSame($this->getPage()->getH1(), $manager->getTitle());
        $this->assertSame('',  $manager->getMainContent()->getChapeau());
        $this->assertSame('<p>',  substr(trim($manager->getMainContent()->getBody()), 0, 3));
    }

    public function testEncryptedLink()
    {
        self::bootKernel();

        $filter = new HtmlEncryptedLink();
        $filter->setApp(self::$kernel->getContainer()->get('pushword.apps')->getApp());
        $filter->setTwig(self::$kernel->getContainer()->get('twig'));
        $this->assertSame(
            'Lorem <span class data-rot=_cvrqjro.pbz/>Test</span> ipsum',
            $filter->convertHtmlRelEncryptedLink('Lorem <a href="https://piedweb.com/" rel="encrypt">Test</a> ipsum')
        );
    }

    private function getManagerPool()
    {
        self::bootKernel();
        $pool = new ManagerPool();
        $pool->apps = self::$kernel->getContainer()->get('pushword.apps');
        $pool->twig = self::$kernel->getContainer()->get('twig');
        $pool->eventDispatcher = self::$kernel->getContainer()->get('event_dispatcher');

        return $pool;
    }

    public function testToc()
    {
        $manager = $this->getManagerPool()->getManager($this->getPage($this->getContentReadyForToc()));

        $this->assertSame('<p>my intro...</p>',  trim($manager->getMainContent()->getIntro()));
        $toCheck = '<h2 id="fist-title">Fist Title</h2>';
        $this->assertSame($toCheck,  substr(trim($manager->getMainContent()->getContent()), 0, \strlen($toCheck)));
    }

    private function getPage($content = null)
    {
        return (new Page())
            ->setH1('Demo Page - Kitchen Sink  Markdown + Twig')
            ->setSlug('kitchen-sink')
            ->setLocale('en')
            ->setCustomProperty('toc', true)
            ->setCreatedAt(new DateTime('1 day ago'))
            ->setUpdatedAt(new DateTime('1 day ago'))
            ->setMainContent($content ?? file_get_contents(__DIR__.'/../../../skeleton/src/DataFixtures/WelcomePage.md'));
    }

    private function getContentReadyForToc()
    {
        return 'my intro...'
            .\chr(10).'## Fist Title'
            .\chr(10).'first paragraph'
            .\chr(10).'## Second Title'
            .\chr(10).'second paragraph';
    }
}
