<?php

namespace App\Controller;

use App\Entity\Author;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiAuthorController extends AbstractController
{
    #[Route('/api/authors', name: 'api_author_list', methods: ['GET'])]
    public function authorList(EntityManagerInterface $em): Response
    {
        $authors = $em->getRepository(Author::class)->findAll();

        $authorsArray = [];
        foreach ($authors as $author) {
            $authorsArray[] = [
                'id' => $author->getId(),
                'firstName' => $author->getFirstName(),
                'middleName' => $author->getMiddleName(),
                'lastName' => $author->getLastName()
            ];
        }

        return new JsonResponse($authorsArray);
    }

    #[Route('/api/authors/create', name: 'api_author_create', methods: ['POST'])]
    public function createAuthor(Request $request, EntityManagerInterface $em): Response
    {
        $data = $request->request->all();

        if (!isset($data['lastName']) || strlen($data['lastName']) < 3) {
            return new JsonResponse(['error' => 'Last name must be at least 3 characters long'], Response::HTTP_BAD_REQUEST);
        }
        if (!isset($data['firstName'])) {
            return new JsonResponse(['error' => 'First name is required'], Response::HTTP_BAD_REQUEST);
        }

        $author = new Author();
        $author->setLastName($data['lastName']);
        $author->setFirstName($data['firstName']);
        $author->setMiddleName($data['middleName'] ?? null);

        try {
            $em->persist($author);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to create author'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['status' => 'Author created'], Response::HTTP_CREATED);
    }
}
