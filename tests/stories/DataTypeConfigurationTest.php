<?php

namespace Sayla\Objects\Tests\Cases;

use Sayla\Objects\Attribute\Property\MapPropertyType;
use Sayla\Objects\DataObject;
use Sayla\Objects\DataType\DataTypeManager;
use Sayla\Objects\DataType\StandardDataType;
use Sayla\Objects\Tests\Support\BaseStory;

class Book extends DataObject
{
    public static function resolveCandyAttribute(self $book)
    {
        return 'candy-' . $book->title;
    }

}

class DataTypeConfigurationTest extends BaseStory
{

    protected function setUp()
    {
        DataObject::clearTriggerCallCount(Book::class);
    }

    public static function setUpBeforeClass()
    {
        $builder = DataTypeManager::getInstance()->getBuilder(Book::class);
        $builder->attributeDefinitions([
            'title:string',
            'author:string',
            'publishDate:datetime' => ['transform.format' => 'Y-m-d', 'map' => 'publish_date'],
            'candy:string' => ['map' => false]
        ]);
        $builder->addPropertyType((new MapPropertyType())->enableAutoMapping());
        $builder->build();
    }

    public function testExtraction(): void
    {
        $dataType = DataTypeManager::getInstance()->get(Book::class);

        $data = $this->getRawBookData();
        /** @var Book $book */
        $book = $dataType->hydrate($data);
        $bookData = $dataType->extract($book);
        $this->assertCount(count($data), $bookData);
        $this->assertEquals($data['title'], $bookData['title']);
        $this->assertEquals($data['title'], $bookData['title']);
        $this->assertEquals($data['author'], $bookData['author']);
        $this->assertEquals($data['publish_date'], $bookData['publish_date']);
    }

    /**
     * @return array
     */
    private function getRawBookData(): array
    {
        return ['title' => 'Better than butter', 'author' => 'Mike Sah', 'publish_date' => '2014-03-01'];
    }

    public function testHydration(): void
    {
        $dataType = DataTypeManager::getInstance()->get(Book::class);

        $data = $this->getRawBookData();
        /** @var Book $book */
        $book = $dataType->hydrate($data);

        /** @var SimpleDataType $dataType */
        $dataType = unserialize(serialize($dataType));
        $this->assertInstanceOf(StandardDataType::class, $dataType);

        $this->assertEquals($data['title'], $book->title);
        $this->assertEquals($data['author'], $book->author);
        $this->assertInstanceOf(\DateTime::class, $book->publishDate);
        $this->assertEquals($data['publish_date'], $book->publishDate->format('Y-m-d'));
        $this->assertEquals('candy-' . $data['title'], $book->candy);

        $this->assertEquals($data['title'], $book['title']);
        $this->assertEquals($data['author'], $book['author']);
        $this->assertInstanceOf(\DateTime::class, $book['publishDate']);
        $this->assertEquals($data['publish_date'], $book['publishDate']->format('Y-m-d'));
        $this->assertEquals('candy-' . $data['title'], $book['candy']);

    }

    public function testMappableProperties(): void
    {
        $dataType = DataTypeManager::getInstance()->get(Book::class);
        $mappable = $dataType->getDescriptor()->getMappable();
        sort($mappable);
        $this->assertEquals(['author', 'publishDate', 'title'], $mappable);
    }
}