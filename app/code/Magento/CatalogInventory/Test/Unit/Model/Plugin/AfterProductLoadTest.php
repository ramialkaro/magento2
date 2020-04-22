<?php declare(strict_types=1);
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogInventory\Test\Unit\Model\Plugin;

use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Plugin\AfterProductLoad;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AfterProductLoadTest extends TestCase
{
    /**
     * @var AfterProductLoad
     */
    protected $plugin;

    /**
     * @var ProductInterface|MockObject
     */
    protected $productMock;

    /**
     * @var ProductExtensionFactory|MockObject
     */
    protected $productExtensionFactoryMock;

    /**
     * @var ProductExtensionInterface|MockObject
     */
    protected $productExtensionMock;

    protected function setUp(): void
    {
        $stockRegistryMock = $this->createMock(StockRegistryInterface::class);
        $this->productExtensionFactoryMock = $this->getMockBuilder(
            ProductExtensionFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new AfterProductLoad(
            $stockRegistryMock,
            $this->productExtensionFactoryMock
        );

        $productId = 5494;
        $stockItemMock = $this->createMock(StockItemInterface::class);

        $stockRegistryMock->expects($this->once())
            ->method('getStockItem')
            ->with($productId)
            ->willReturn($stockItemMock);

        $this->productExtensionMock = $this->getMockBuilder(ProductExtensionInterface::class)
            ->setMethods(['setStockItem'])
            ->getMockForAbstractClass();
        $this->productExtensionMock->expects($this->once())
            ->method('setStockItem')
            ->with($stockItemMock)
            ->willReturnSelf();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->productExtensionMock)
            ->willReturnSelf();
        $this->productMock->expects(($this->once()))
            ->method('getId')
            ->will($this->returnValue($productId));
    }

    public function testAfterLoad()
    {
        $this->productMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->productExtensionMock);
        $this->productExtensionFactoryMock->expects($this->never())
            ->method('create');

        $this->assertEquals(
            $this->productMock,
            $this->plugin->afterLoad($this->productMock)
        );
    }
}
