<?php

use App\Entity\Author;
use App\Entity\Book;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookTest extends WebTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * @dataProvider providerAdd
     */
    public function testAdd(array $data, int $status_code, bool $success, string $error = '')
    {
        if (isset($data['authors'])) {
            $authors = [];
            foreach ($data['authors'] as $author) {
                if ($author === null) {
                    $authors[] = -1;
                } else {
                    $authorEntity = new Author();
                    foreach ($author['name'] as $lang => $name) {
                        $authorEntity->translate($lang)->setName($name);
                    }
                    $this->entityManager->persist($authorEntity);
                    $authorEntity->mergeNewTranslations();
                    $authors[] = $authorEntity->getId();
                }
            }
            $this->entityManager->flush();
            $data['authors'] = $authors;
        }

        $this->client->request('POST', '/book/create', [], [], [], json_encode($data, JSON_THROW_ON_ERROR));

        self::assertEquals($status_code, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($response);
        self::assertArrayHasKey('success', $response);
        self::assertIsBool($response['success']);

        self::assertEquals($success, $response['success']);

        if ($success) {
            self::assertArrayHasKey('data', $response);
            self::assertIsArray($response['data']);
            self::assertArrayHasKey('id', $response['data']);
            self::assertIsInt($response['data']['id']);
        } else {
            self::assertArrayHasKey('errors', $response);
            self::assertIsArray($response['errors']);
            self::assertCount(1, $response['errors']);
            self::assertEquals($error, $response['errors'][0]);
        }
    }

    public function providerAdd()
    {
        return [
            [
                [
                    'name' => [
                        'ru' => 'тест 1',
                        'en' => 'test 1',
                    ],
                    'authors' => [
                        [
                            'name' => [
                                'ru' => 'тест 1',
                                'en' => 'test 1',
                            ],
                        ],
                    ],
                ],
                200,
                true,
            ],
            [
                [
                    'name' => [
                        'ru' => 'тест 2',
                    ],
                    'authors' => [
                        [
                            'name' => [
                                'ru' => 'тест 2',
                            ],
                        ],
                    ],
                ],
                200,
                true,
            ],
            [
                [
                    'name' => [
                        'de' => 'prüfung 2',
                    ],
                    'authors' => [
                        [
                            'name' => [
                                'de' => 'prüfung 2',
                            ],
                        ],
                    ],
                ],
                200,
                true,
            ],
            [
                [
                    'name' => [
                        'de' => 'prüfung 2',
                    ],
                    'authors' => [
                        null,
                    ],
                ],
                400,
                false,
                'Author not found'
            ],
            /*[
                [
                    'name' => [],
                ],
                400,
                false,
                'You must specify a name',
            ],
            [
                [],
                400,
                false,
                'You must specify a name',
            ],*/
        ];
    }

    /**
     * @depends testAdd
     */
    public function testGet()
    {
        $this->client->request('GET', '/ru/book/-1');

        static::assertEquals(404, $this->client->getResponse()->getStatusCode());

        $authorEntity = new Author();
        $authorEntity->translate('ru')->setName('Автор тест');
        $authorEntity->translate('en')->setName('Author test');
        $this->entityManager->persist($authorEntity);
        $authorEntity->mergeNewTranslations();

        $book = new Book();
        $book->addAuthor($authorEntity);
        $book->translate('ru')->setName('Книга тест');
        $book->translate('en')->setName('Book test');
        $this->entityManager->persist($book);
        $book->mergeNewTranslations();
        $this->entityManager->flush();

        $this->client->request('GET', '/en/book/' . $book->getId());

        static::assertEquals(200, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($response);
        self::assertArrayHasKey('success', $response);
        self::assertIsBool($response['success']);

        self::assertTrue($response['success']);

        self::assertArrayHasKey('data', $response);
        self::assertIsArray($response['data']);
        self::assertArrayHasKey('id', $response['data']);
        self::assertIsInt($response['data']['id']);

        static::assertEquals($book->getId(), $response['data']['id']);
        self::assertArrayHasKey('id', $response['data']);
        static::assertEquals($response['data']['name'], 'Book test');
    }

    /**
     * @depends testGet
     */
    public function testSearch()
    {
        $authorEntity = new Author();
        $authorEntity->translate('ru')->setName('Автор тест');
        $authorEntity->translate('en')->setName('Author test');
        $this->entityManager->persist($authorEntity);
        $authorEntity->mergeNewTranslations();

        $book = new Book();
        $book->addAuthor($authorEntity);
        $book->translate('ru')->setName('Книга тест');
        $book->translate('en')->setName('Book test 99999999999999000111');
        $this->entityManager->persist($book);
        $book->mergeNewTranslations();
        $this->entityManager->flush();

        $this->client->request('GET', '/en/book/search?query=99999999999999000111');

        static::assertEquals(200, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($response);
        self::assertArrayHasKey('success', $response);
        self::assertIsBool($response['success']);

        self::assertTrue($response['success']);

        self::assertArrayHasKey('data', $response);
        self::assertIsArray($response['data']);
        self::assertArrayHasKey('list', $response['data']);
        self::assertIsArray($response['data']['list']);
        self::assertTrue(count($response['data']['list']) > 0); // @todo: Нужно сделать что-то более "интересное"


        $this->client->request('GET', '/en/book/search?query=99999999999999000111123');

        static::assertEquals(404, $this->client->getResponse()->getStatusCode());
    }
}