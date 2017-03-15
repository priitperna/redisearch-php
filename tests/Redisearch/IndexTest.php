<?php

namespace Eeh\Tests\Redisearch;

use Eeh\Redisearch\Exceptions\NoFieldsInIndexException;
use Eeh\Redisearch\Fields\TextField;
use Eeh\Redisearch\IndexInterface;
use Eeh\Tests\Stubs\BookIndex;
use Eeh\Tests\Stubs\IndexWithoutFields;
use PHPUnit\Framework\TestCase;
use Redis;

class ClientTest extends TestCase
{
    private $indexName;
    /** @var IndexInterface */
    private $subject;
    /** @var Redis */
    private $redis;

    public function setUp()
    {
        $this->indexName = 'ClientTest';
        $this->redis = new Redis();
        $this->redis->connect(getenv('REDIS_HOST') ?? '127.0.0.1', getenv('REDIS_PORT') ?? 6379);
        $this->subject = (new BookIndex($this->indexName))->setRedis($this->redis);
    }

    public function tearDown()
    {
        $this->redis->flushAll();
    }

    public function testShouldFailToCreateIndexWhenThereAreNoFieldsDefined()
    {
        $this->expectException(NoFieldsInIndexException::class);

        (new IndexWithoutFields())->create();
    }

    public function testShouldCreateIndex()
    {
        $result = $this->subject->create();

        $this->assertTrue($result);
    }

    public function testAddDocument()
    {
        $this->subject->create();

        $result = $this->subject->addDocument([
            new TextField('title', 'How to be awesome.'),
            new TextField('author', 'Jack'),
        ]);

        $this->assertTrue($result);
    }

    public function testSearch()
    {
        $this->subject->create();
        $this->subject->addDocument([
            new TextField('title', 'How to be awesome: Part 1.'),
            new TextField('author', 'Jack'),
        ]);
        $this->subject->addDocument([
            new TextField('title', 'How to be awesome: Part 2.'),
            new TextField('author', 'Jack'),
        ]);

        $result = $this->subject->search('awesome');

        $this->assertEquals($result->getCount(), 2);
    }
}