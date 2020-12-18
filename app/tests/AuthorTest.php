<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthorTest extends WebTestCase
{
    /**
     * @dataProvider providerAdd
     */
    public function testAdd(array $data, int $status_code, bool $success, string $error = '')
    {
        $client = static::createClient();

        $client->request('POST', '/author/create', [], [], [], json_encode($data, JSON_THROW_ON_ERROR));

        self::assertEquals($status_code, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

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
                ],
                200,
                true,
            ],
            [
                [
                    'name' => [
                        'ru' => 'тест 2',
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
                ],
                200,
                true,
            ],
            [
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
            ],
        ];
    }
}