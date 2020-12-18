<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $manager->getConnection()->getConfiguration()->setSQLLogger(null);

        gc_collect_cycles();

        $author_ids = [];

        for ($i = 0; $i < 10000; $i++) {
            $author = new Author();
            $author->translate('ru')->setName('Автор ' . $i);
            $author->translate('en')->setName('Author ' . $i);
            $manager->persist($author);
            $author_ids[$author->getId()] = $i;
            $author->mergeNewTranslations();

            if ($i % 200 === 0) {
                $manager->flush();
                $manager->clear();
            }
            if ($i % 2000 === 0) {
                gc_collect_cycles();
            }
        }

        $manager->flush();
        $manager->clear();

        gc_collect_cycles();

        $authorRepository = $manager->getRepository(Author::class);

        for ($i = 0; $i < 10000; $i++) {
            $book = new Book();
            $book->translate('ru')->setName('Книга ' . $i);
            $book->translate('en')->setName('Book ' . $i);

            /** @var Author[] $authors */
            $authors = $authorRepository->findBy(['id' => array_rand($author_ids, random_int(1, 5))]);
            foreach ($authors as $author) {
                $book->addAuthor($author);
            }

            $manager->persist($book);
            $book->mergeNewTranslations();

            if ($i % 200 === 0) {
                $manager->flush();
                $manager->clear();
            }
            if ($i % 2000 === 0) {
                gc_collect_cycles();
            }
        }
        $manager->flush();
        $manager->clear();
    }
}
