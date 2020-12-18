<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends Controller
{
    /**
     * @Route("/book/create", name="book_create", methods={"POST"})
     */
    public function createBook(Request $request, EntityManagerInterface $entityManager, AuthorRepository $authorRepository): Response
    {
        try {
            $request = $this->transformJsonBody($request);

            if (!$request || !$request->get('name')) {
                throw new \Exception('You must specify a name');
            }
            if (!$request->get('authors')) {
                throw new \Exception('You must specify a authors');
            }

            $book = new Book();
            $authors = $authorRepository->findBy([
                'id' => $request->get('authors'),
            ]);
            if (count($authors) != count($request->get('authors'))) {
                throw new \Exception('Author not found');
            }
            foreach ($authors as $author) {
                $book->addAuthor($author);
            }
            foreach ($request->get('name') as $locale => $name) {
                $book->translate($locale)->setName($name);
            }
            $entityManager->persist($book);
            $book->mergeNewTranslations();
            $entityManager->flush();

            return $this->response([
                'success' => true,
                'data' => [
                    'id' => $book->getId()
                ],
                'errors' => null
            ]);
        } catch (\Exception $e) {
            return $this->response([
                'success' => false,
                'data' => null,
                'errors' => [$e->getMessage()],
            ], 400);
        }
    }

    /**
     * @Route("/{_locale}/book/search", priority=10, name="book_search", methods={"GET"})
     *
     * @param $id
     * @param Request $request
     * @param BookRepository $bookRepository
     * @return Response
     */
    public function searchBook(Request $request, BookRepository $bookRepository): Response
    {
        $books = $bookRepository->search($request->get('query'), $request->getLocale());

        if (count($books) <= 0) {
            $data = [
                'success' => false,
                'errors' => [],
            ];
            return $this->response($data, 404);
        }

        $list = [];
        foreach ($books as $book) {
            $authors = [];
            foreach ($book->getAuthor() as $author) {
                $authors[] = array(
                    'id' => $author->getId(),
                    'name' => $author->getName(),
                );
            }
            $list[] = [
                'id' => $book->getId(),
                'name' => $book->getName(),
                'authors' => $authors,
            ];
        }

        return $this->response([
            'success' => true,
            'data' => [
                'list' => $list,
            ],
        ]);
    }

    /**
     * @Route("/{_locale}/book/{id}", priority=1, name="book_show", methods={"GET"})
     *
     * @param $id
     * @param Request $request
     * @param BookRepository $bookRepository
     *
     * @return Response
     */
    public function show($id, Request $request, BookRepository $bookRepository): Response
    {
        $book = $bookRepository->find($id);

        if (!$book) {
            $data = [
                'success' => false,
                'errors' => [],
            ];
            return $this->response($data, 404);
        }

        $book->translate($request->getLocale());

        $authors = [];
        foreach ($book->getAuthor() as $author) {
            $authors[] = array(
                'id' => $author->getId(),
                'name' => $author->getName(),
            );
        }

        return $this->response([
            'success' => true,
            'data' => [
                'id' => $book->getId(),
                'name' => $book->getName(),
                'authors' => $authors,
            ],
        ]);
    }
}
