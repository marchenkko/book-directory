<?php

namespace App\Controller;

use App\Entity\Author;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthorController extends AbstractController
{
    #[Route('/authors', name: 'author_list')]
    public function authorList(EntityManagerInterface $em): Response
    {
        $authors = $em->getRepository(Author::class)->findAll();
        return $this->render('author/list.html.twig', [
            'authors' => $authors,
        ]);
    }

    #[Route('/authors/create', name: 'author_create_form')]
    public function createAuthorForm(Request $request): Response
    {
        $error = $request->query->get('error');
        return $this->render('author/create.html.twig', [
            'error' => $error,
        ]);
    }

    #[Route('/authors/create/post', name: 'author_create', methods: ['POST'])]
    public function createAuthor(Request $request, EntityManagerInterface $em): Response
    {
        $data = $request->request->all();

        if (!isset($data['lastName']) || strlen($data['lastName']) < 3) {
            return $this->redirectToRoute('author_create_form', ['error' => 'Last name must be at least 3 characters long']);
        }
        if (!isset($data['firstName'])) {
            return $this->redirectToRoute('author_create_form', ['error' => 'First name is required']);
        }

        $author = new Author();
        $author->setLastName($data['lastName']);
        $author->setFirstName($data['firstName']);
        $author->setMiddleName($data['middleName'] ?? null);

        try {
            $em->persist($author);
            $em->flush();
        } catch (\Exception $e) {
            return $this->redirectToRoute('author_create_form', ['error' => 'Failed to create author']);
        }

        return $this->redirectToRoute('home');
    }
}
