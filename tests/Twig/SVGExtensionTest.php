<?php

declare(strict_types=1);

namespace Pushword\Core\Tests\Twig;

use Pushword\Core\Twig\SVGExtension;
use Pushword\Core\Utils\FontAwesome5To6;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SVGExtensionTest extends KernelTestCase
{
    public function testFontAwesome5To6(): void
    {
        $this->assertSame('file-lines', FontAwesome5To6::convertNameFromFontAwesome5To6('file-alt'));
    }

    public function testTwigExtension(): void
    {
        self::bootKernel();
        $twig = new SVGExtension(self::$kernel->getContainer()->get(\Pushword\Core\Component\App\AppPool::class));
        $this->assertStringStartsWith('<svg', $twig->getSvg('facebook'));
    }
}
