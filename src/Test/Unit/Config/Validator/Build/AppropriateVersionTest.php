<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Build\AppropriateVersion;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class AppropriateVersionTest extends TestCase
{
    /**
     * @var AppropriateVersion
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersion;

    /**
     * @var BuildInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->magentoVersion = $this->createMock(MagentoVersion::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);

        $this->validator = new AppropriateVersion(
            $this->resultFactoryMock,
            $this->magentoVersion,
            $this->stageConfigMock
        );
    }

    public function testValidateVersionGreaterTwoDotTwo()
    {
        $this->magentoVersion->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->stageConfigMock->expects($this->never())
            ->method('get');

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateVersionLowerTwoDotTwoAndStrategyEmpty()
    {
        $this->magentoVersion->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willReturn(false);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(StageConfigInterface::VAR_SCD_STRATEGY)
            ->willReturn(null);

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateVersionLowerTwoDotTwoAndStrategyConfigured()
    {
        $this->magentoVersion->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willReturn(false);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(StageConfigInterface::VAR_SCD_STRATEGY)
            ->willReturn('quick');
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'Some configuration is not suitable with current version of magento',
                'The variable SCD_STRATEGY is allowed from magento version 2.2.0'
            );

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }
}
