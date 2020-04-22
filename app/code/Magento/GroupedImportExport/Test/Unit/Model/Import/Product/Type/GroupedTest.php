<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GroupedImportExport\Test\Unit\Model\Import\Product\Type;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\GroupedImportExport;
use Magento\GroupedImportExport\Model\Import\Product\Type\Grouped;
use Magento\GroupedImportExport\Model\Import\Product\Type\Grouped\Links;
use Magento\ImportExport\Test\Unit\Model\Import\AbstractImportTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GroupedTest extends AbstractImportTestCase
{
    /** @var GroupedImportExport\Model\Import\Product\Type\Grouped */
    protected $grouped;

    /**
     * @var MockObject
     */
    protected $setCollectionFactory;

    /**
     * @var Collection|MockObject
     */
    protected $setCollection;

    /**
     * @var MockObject
     */
    protected $attrCollectionFactory;

    /**
     * @var Mysql|MockObject
     */
    protected $connection;

    /**
     * @var Select|MockObject
     */
    protected $select;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resource;

    /**
     * @var []
     */
    protected $params;

    /**
     * @var GroupedImportExport\Model\Import\Product\Type\Grouped\Links|MockObject
     */
    protected $links;

    /**
     * @var Product|MockObject
     */
    protected $entityModel;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setCollectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->setCollection = $this->createPartialMock(
            Collection::class,
            ['setEntityTypeFilter']
        );
        $this->setCollectionFactory->expects($this->any())->method('create')->will(
            $this->returnValue($this->setCollection)
        );
        $this->setCollection->expects($this->any())->method('setEntityTypeFilter')->will($this->returnValue([]));
        $this->attrCollectionFactory = $this->createPartialMock(
            \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory::class,
            ['create', 'addFieldToFilter']
        );
        $this->attrCollectionFactory->expects($this->any())->method('create')->will($this->returnSelf());
        $this->attrCollectionFactory->expects($this->any())->method('addFieldToFilter')->willReturn([]);
        $this->entityModel = $this->createPartialMock(
            Product::class,
            ['getErrorAggregator', 'getNewSku', 'getOldSku', 'getNextBunch', 'isRowAllowedToImport', 'getRowScope']
        );
        $this->entityModel->method('getErrorAggregator')->willReturn($this->getErrorAggregatorObject());
        $this->params = [
            0 => $this->entityModel,
            1 => 'grouped'
        ];
        $this->links = $this->createMock(Links::class);
        $entityAttributes = [
            [
                'attribute_set_name' => 'attribute_id',
                'attribute_id' => 'attributeSetName',
            ]
        ];
        $this->connection = $this->createPartialMock(
            Mysql::class,
            ['select', 'fetchAll', 'fetchPairs', 'joinLeft', 'insertOnDuplicate', 'delete', 'quoteInto']
        );
        $this->select = $this->createPartialMock(
            Select::class,
            ['from', 'where', 'joinLeft', 'getConnection']
        );
        $this->select->expects($this->any())->method('from')->will($this->returnSelf());
        $this->select->expects($this->any())->method('where')->will($this->returnSelf());
        $this->select->expects($this->any())->method('joinLeft')->will($this->returnSelf());
        $this->connection->expects($this->any())->method('select')->will($this->returnValue($this->select));
        $connectionMock = $this->createMock(Mysql::class);
        $connectionMock->expects($this->any())->method('quoteInto')->will($this->returnValue('query'));
        $this->select->expects($this->any())->method('getConnection')->willReturn($connectionMock);
        $this->connection->expects($this->any())->method('insertOnDuplicate')->willReturnSelf();
        $this->connection->expects($this->any())->method('delete')->willReturnSelf();
        $this->connection->expects($this->any())->method('quoteInto')->willReturn('');
        $this->connection->expects($this->any())->method('fetchAll')->will($this->returnValue($entityAttributes));
        $this->resource = $this->createPartialMock(
            ResourceConnection::class,
            ['getConnection', 'getTableName']
        );
        $this->resource->expects($this->any())->method('getConnection')->will($this->returnValue($this->connection));
        $this->resource->expects($this->any())->method('getTableName')->will($this->returnValue('tableName'));
        $this->grouped = $this->objectManagerHelper->getObject(
            Grouped::class,
            [
                'attrSetColFac' => $this->setCollectionFactory,
                'prodAttrColFac' => $this->attrCollectionFactory,
                'resource' => $this->resource,
                'params' => $this->params,
                'links' => $this->links
            ]
        );
        $metadataPoolMock = $this->createMock(MetadataPool::class);
        $entityMetadataMock = $this->createMock(EntityMetadata::class);
        $metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($entityMetadataMock);
        $entityMetadataMock->expects($this->any())
            ->method('getLinkField')
            ->willReturn('entity_id');
        $entityMetadataMock->expects($this->any())
            ->method('getIdentifierField')
            ->willReturn('entity_id');
        $reflection = new \ReflectionClass(Grouped::class);
        $reflectionProperty = $reflection->getProperty('metadataPool');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->grouped, $metadataPoolMock);
    }

    /**
     * Test for method saveData()
     *
     * @param array $skus
     * @param array $bunch
     *
     * @dataProvider saveDataProvider
     */
    public function testSaveData($skus, $bunch)
    {
        $this->entityModel->expects($this->once())->method('getNewSku')->will($this->returnValue($skus['newSku']));
        $this->entityModel->expects($this->once())->method('getOldSku')->will($this->returnValue($skus['oldSku']));
        $attributes = ['position' => ['id' => 0], 'qty' => ['id' => 0]];
        $this->links->expects($this->once())->method('getAttributes')->will($this->returnValue($attributes));

        $this->entityModel->expects($this->at(2))->method('getNextBunch')->will($this->returnValue([$bunch]));
        $this->entityModel->expects($this->any())->method('isRowAllowedToImport')->will($this->returnValue(true));
        $this->entityModel->expects($this->any())->method('getRowScope')->will($this->returnValue(
            Product::SCOPE_DEFAULT
        ));

        $this->links->expects($this->once())->method('saveLinksData');
        $this->grouped->saveData();
    }

    /**
     * Data provider for saveData()
     *
     * @return array
     */
    public function saveDataProvider()
    {
        return [
            [
                'skus' => [
                    'newSku' => [
                        'sku_assoc1' => ['entity_id' => 1],
                        'productsku' => ['entity_id' => 3, 'attr_set_code' => 'Default', 'type_id' => 'grouped']
                    ],
                    'oldSku' => ['sku_assoc2' => ['entity_id' => 2]]
                ],
                'bunch' => [
                    'associated_skus' => 'sku_assoc1=1, sku_assoc2=2',
                    'sku' => 'productsku',
                    'product_type' => 'grouped'
                ]
            ],
            [
                'skus' => [
                    'newSku' => [
                        'productsku' => ['entity_id' => 1, 'attr_set_code' => 'Default', 'type_id' => 'grouped']
                    ],
                    'oldSku' => []
                ],
                'bunch' => [
                    'associated_skus' => '',
                    'sku' => 'productsku',
                    'product_type' => 'grouped'
                ]
            ],
            [
                'skus' => ['newSku' => [],'oldSku' => []],
                'bunch' => [
                    'associated_skus' => 'sku_assoc1=1, sku_assoc2=2',
                    'sku' => 'productsku',
                    'product_type' => 'grouped'
                ]
            ],
            [
                'skus' => [
                    'newSku' => [
                        'sku_assoc1' => ['entity_id' => 1],
                        'productsku' => ['entity_id' => 3, 'attr_set_code' => 'Default', 'type_id' => 'grouped']
                    ],
                    'oldSku' => []
                ],
                'bunch' => [
                    'associated_skus' => 'sku_assoc1=1',
                    'sku' => 'productsku',
                    'product_type' => 'simple'
                ]
            ]
        ];
    }

    /**
     * Test saveData() with store row scope
     */
    public function testSaveDataScopeStore()
    {
        $this->entityModel->expects($this->once())->method('getNewSku')->will($this->returnValue([
            'sku_assoc1' => ['entity_id' => 1],
            'productsku' => ['entity_id' => 2, 'attr_set_code' => 'Default', 'type_id' => 'grouped']
        ]));
        $this->entityModel->expects($this->once())->method('getOldSku')->will($this->returnValue([
            'sku_assoc2' => ['entity_id' => 3]
        ]));
        $attributes = ['position' => ['id' => 0], 'qty' => ['id' => 0]];
        $this->links->expects($this->once())->method('getAttributes')->will($this->returnValue($attributes));

        $bunch = [[
            'associated_skus' => 'sku_assoc1=1, sku_assoc2=2',
            'sku' => 'productsku',
            'product_type' => 'grouped'
        ]];
        $this->entityModel->expects($this->at(2))->method('getNextBunch')->will($this->returnValue($bunch));
        $this->entityModel->expects($this->any())->method('isRowAllowedToImport')->will($this->returnValue(true));
        $this->entityModel->expects($this->at(4))->method('getRowScope')->will($this->returnValue(
            Product::SCOPE_DEFAULT
        ));
        $this->entityModel->expects($this->at(5))->method('getRowScope')->will($this->returnValue(
            Product::SCOPE_STORE
        ));

        $this->links->expects($this->once())->method('saveLinksData');
        $this->grouped->saveData();
    }
}
