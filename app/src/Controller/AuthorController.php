<?php

namespace App\Controller;

use App\Entity\Author;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

class AuthorController extends Controller
{
    /**
     * @Route("/author/create", name="author_create", methods={"POST"})
     */
    public function createAuthor(Request $request, EntityManagerInterface $entityManager): Response
    {
        try {
            $request = $this->transformJsonBody($request);

            if (!$request || !$request->get('name')) {
                throw new \Exception('You must specify a name');
            }

            $author = new Author();
            foreach ($request->get('name') as $locale => $name) {
                $author->translate($locale)->setName($name);
            }
            $entityManager->persist($author);
            $author->mergeNewTranslations();
            $entityManager->flush();

            return $this->response([
                'success' => true,
                'data' => [
                    'id' => $author->getId()
                ],
                'errors' => null
            ]);

        } catch (\Exception $e) {
            return $this->response([
                'success' => false,
                'errors' => [$e->getMessage()],
            ], 400);
        }
    }
}
