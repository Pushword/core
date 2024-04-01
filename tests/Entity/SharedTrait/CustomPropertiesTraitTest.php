<?php

namespace Pushword\Core\Tests\Entity\SharedTrait;

use PHPUnit\Framework\TestCase;
use Pushword\Core\Entity\Page;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Component\Yaml\Yaml;

class CustomPropertiesTraitTest extends TestCase
{
    protected static function customPorperties($firstValue = 'test', $secondValue = 'test 2')
    {
        return [
            'newCustomPropertyNotIndexed' => $firstValue,
            'customProperties' => $secondValue,
        ];
    }

    protected static function standStandAloneCustomProperties($firstValue = 'test')
    {
        return Yaml::dump(['newCustomPropertyNotIndexed' => $firstValue]);
    }

    public function testStandAloneCustomProperties()
    {
        $customProperties = new Page();

        $this->assertEmpty($customProperties->getCustomProperties());

        $customProperties->setCustomProperties(static::customPorperties());

        $this->assertNull($customProperties->validateCustomProperties($this->getExceptionContextInterface()));
        $this->assertSame($customProperties->getCustomProperties(), static::customPorperties());
        $this->assertSame($customProperties->getStandAloneCustomProperties(), static::standStandAloneCustomProperties());

        $customProperties->setStandAloneCustomProperties(static::standStandAloneCustomProperties('test 1234'), true);
        $this->assertSame(static::customPorperties('test 1234'), $customProperties->getCustomProperties());

        $this->assertFalse($customProperties->isStandAloneCustomProperty('customProperties'));

        $customProperties->removeCustomProperty('newCustomPropertyNotIndexed');
        $this->assertArrayNotHasKey('newCustomPropertyNotIndexed', $customProperties->getCustomProperties());
    }

    /**
     * @return ExecutionContextInterface
     */
    protected function getExceptionContextInterface()
    {
        $mockConstraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $mockConstraintViolationBuilder->method('atPath')->willReturnSelf();
        $mockConstraintViolationBuilder->method('addViolation')->willReturnSelf();

        $mock = $this->createMock(ExecutionContextInterface::class);
        $mock->method('buildViolation')->willReturnCallback(function ($arg) use ($mockConstraintViolationBuilder) {
            if (\in_array($arg, ['page.customProperties.malformed', 'page.customProperties.notStandAlone'])) {
                new \Error();
            } else {
                return $mockConstraintViolationBuilder;
            }
        });

        return $mock;
    }
}
