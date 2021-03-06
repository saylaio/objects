<?php

namespace Sayla\Objects\Tests\Cases;

use Sayla\Objects\Contract\ObjectStore;
use Sayla\Objects\DataModel;
use Sayla\Objects\DataObject;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Objects\Tests\Support\BaseStory;

class MutableBookModel extends DataModel
{
    public static function resolveCandyAttribute(self $book)
    {
        return 'candy-' . $book->title . $book->getKey();
    }

    protected function determineExistence(): bool
    {
        return $this->id > 0;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->id;
    }
}

class MutableObjectStoreTest extends BaseStory
{
    protected function setUp()
    {
        DataObject::clearTriggerCallCount(MutableBookModel::class);
    }

    public function testCreate()
    {
        $storeStrategy = $this->getMockBuilder(ObjectStore::class)
            ->setMethods(['create'])
            ->getMock();
        $storeStrategy->expects($this->once())
            ->method('create')
            ->willReturnCallback(function (MutableBookModel $bookModel) {
                $this->assertFalse($bookModel->exists());
                $this->assertTrue($bookModel->isStoring());
                return ['id' => 99];
            });

        $dataType = $this->getDataType($storeStrategy);

        $data = $this->getRawBookData();
        /** @var MutableBookModel $book */
        $book = $dataType->hydrate($data);

        $this->assertSame($book, $book->create());

        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'beforeCreate'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'create'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'afterCreate'));

        $this->assertTrue($book->exists());
        $this->assertSame(99, $book->id);
        $this->assertEquals("candy-{$data['title']}99", $book->candy);
    }

    /**
     * @param $storeStrategy
     * @return \Sayla\Objects\DataType\StoringDataType
     */
    private function getDataType($storeStrategy): \Sayla\Objects\DataType\StoringDataType
    {
        $dataTypeManager = new DataTypeManager();
        $builder = $dataTypeManager->getStorableBuilder(MutableBookModel::class, $storeStrategy);
        $builder->attributeDefinitions([
            'id:pk' => ['mapTo' => '_id'],
            'title:string',
            'author:string',
            'publishDate:datetime' => ['transform.format' => 'Y-m-d', 'mapTo' => 'publish_date'],
            'candy:string' => ['map' => false]
        ])->onOptionsResolution(function ($options) {
            $options['propertyTypes']['map']->enableAutoMapping();
        });
        return $builder->build();
    }

    /**
     * @return array
     */
    private function getRawBookData(): array
    {
        return ['title' => 'Better than butter', 'author' => 'Mike Sah', 'publish_date' => '2014-03-01'];
    }

    public function testDelete()
    {
        $storeStrategy = $this->getMockBuilder(ObjectStore::class)
            ->setMethods(['create', 'delete'])
            ->getMock();
        $storeStrategy->expects($this->once())
            ->method('create')
            ->willReturnCallback(function () {
                return ['id' => 99];
            });
        $storeStrategy->expects($this->once())
            ->method('delete')
            ->willReturnCallback(function (MutableBookModel $bookModel) {
                $this->assertTrue($bookModel->exists());
                $this->assertTrue($bookModel->isStoring());
            });

        $dataType = $this->getDataType($storeStrategy);

        $data = $this->getRawBookData();
        /** @var MutableBookModel $book */
        $book = $dataType->hydrate($data);
        $book->create();

        $this->assertSame($book, $book->delete());

        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'beforeDelete'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'delete'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'afterDelete'));

        $this->assertSame($book, $book);
        $this->assertSame(99, $book->id);
        $this->assertFalse($book->exists());

    }

    public function testSave()
    {
        $storeStrategy = $this->getMockBuilder(ObjectStore::class)
            ->setMethods(['create', 'update'])
            ->getMock();
        $storeStrategy->expects($this->once())
            ->method('create')
            ->willReturnCallback(function (MutableBookModel $bookModel) {
                $this->assertFalse($bookModel->exists());
                $this->assertTrue($bookModel->isStoring());
                return ['id' => 99];
            });
        $storeStrategy->expects($this->once())
            ->method('update')
            ->willReturnCallback(function (MutableBookModel $bookModel) {
                $this->assertTrue($bookModel->exists());
                $this->assertTrue($bookModel->isStoring());
                return ['id' => 99];
            });

        $dataType = $this->getDataType($storeStrategy);

        $data = $this->getRawBookData();
        /** @var MutableBookModel $book */
        $book = $dataType->hydrate($data);

        $this->assertSame($book, $book->save());

        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'beforeSave'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'save'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'afterSave'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'beforeCreate'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'create'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'afterCreate'));
        $this->assertTrue($book->exists());
        $this->assertSame(99, $book->id);

        $this->assertSame($book, $book->save());

        $this->assertSame(2, DataObject::getTriggerCallCount($book->getDataType(), 'beforeSave'));
        $this->assertSame(2, DataObject::getTriggerCallCount($book->getDataType(), 'save'));
        $this->assertSame(2, DataObject::getTriggerCallCount($book->getDataType(), 'afterSave'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'beforeUpdate'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'update'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'afterUpdate'));

    }

    public function testUpdate()
    {
        $storeStrategy = $this->getMockBuilder(ObjectStore::class)
            ->setMethods(['create', 'update'])
            ->getMock();
        $storeStrategy->expects($this->once())
            ->method('create')
            ->willReturnCallback(function () {
                return ['id' => 99];
            });
        $storeStrategy->expects($this->once())
            ->method('update')
            ->willReturnCallback(function (MutableBookModel $bookModel) {
                $this->assertTrue($bookModel->exists());
                $this->assertTrue($bookModel->isStoring());
            });

        $dataType = $this->getDataType($storeStrategy);

        $data = $this->getRawBookData();
        /** @var MutableBookModel $book */
        $book = $dataType->hydrate($data);
        $book->create();

        $this->assertSame($book, $book->update());

        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'beforeUpdate'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'update'));
        $this->assertSame(1, DataObject::getTriggerCallCount($book->getDataType(), 'afterUpdate'));

        $this->assertTrue($book->exists());

    }
}