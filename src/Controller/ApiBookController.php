<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Author;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiBookController extends AbstractController
{
    #[Route('/api/books', name: 'api_book_list', methods: ['GET'])]
    public function bookList(EntityManagerInterface $em): JsonResponse
    {
        $books = $em->getRepository(Book::class)->findAll();

        $booksArray = [];
        foreach ($books as $book) {
            $booksArray[] = [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'shortDescription' => $book->getShortDescription(),
                'publicationDate' => $book->getPublicationDate()->format('Y-m-d'),
                'image' => $book->getImage()
            ];
        }

        return new JsonResponse($booksArray);
    }

    #[Route('/api/books/create', name: 'api_book_create', methods: ['POST'])]
    public function createBook(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->request->all();

        if (!isset($data['title']) || empty($data['title'])) {
            return new JsonResponse(['error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['publicationDate']) || empty($data['publicationDate'])) {
            return new JsonResponse(['error' => 'Publication date is required'], Response::HTTP_BAD_REQUEST);
        }

        $book = new Book();
        $book->setTitle($data['title']);
        $book->setShortDescription($data['shortDescription'] ?? '');
        $book->setPublicationDate(new \DateTime($data['publicationDate']));

        try {
            $em->persist($book);
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to create book'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['status' => 'Book created'], Response::HTTP_CREATED);
    }

    #[Route('/api/books/{id}', name: 'api_book_show', methods: ['GET'])]
    public function showBook($id, EntityManagerInterface $em): JsonResponse
    {
        $book = $em->getRepository(Book::class)->find($id);

        if (!$book) {
            return new JsonResponse(['error' => 'The book does not exist'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $book->getId(),
            'title' => $book->getTitle(),
            'shortDescription' => $book->getShortDescription(),
            'publicationDate' => $book->getPublicationDate()->format('Y-m-d'),
            'image' => $book->getImage()
        ]);
    }

    #[Route('/api/books/{id}/edit', name: 'api_book_edit', methods: ['POST'])]
    public function editBook($id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $book = $em->getRepository(Book::class)->find($id);

        if (!$book) {
            return new JsonResponse(['error' => 'The book does not exist'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->request->all();

        $initialTitle = $book->getTitle();
        $initialShortDescription = $book->getShortDescription();
        $initialPublicationDate = $book->getPublicationDate()->format('Y-m-d');

        $newTitle = $data['title'] ?? $initialTitle;
        $newShortDescription = $data['shortDescription'] ?? $initialShortDescription;
        $newPublicationDate = new \DateTime($data['publicationDate'] ?? $initialPublicationDate);

        if ($initialTitle === $newTitle && $initialShortDescription === $newShortDescription && $initialPublicationDate === $newPublicationDate->format('Y-m-d')) {
            return new JsonResponse(['error' => 'No changes were made to the book'], Response::HTTP_BAD_REQUEST);
        }

        $book->setTitle($newTitle);
        $book->setShortDescription($newShortDescription);
        $book->setPublicationDate($newPublicationDate);

        try {
            $em->flush();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to update book'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['status' => 'Book updated']);
    }

    #[Route('/api/books/author/{id}', name: 'api_books_by_author', methods: ['GET'])]
    public function showBooksByAuthor($id, EntityManagerInterface $em): JsonResponse
    {
        $author = $em->getRepository(Author::class)->find($id);

        if (!$author) {
            return new JsonResponse(['error' => 'The author does not exist'], Response::HTTP_NOT_FOUND);
        }

        $booksArray = [];
        foreach ($author->getBooks() as $book) {
            $booksArray[] = [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'shortDescription' => $book->getShortDescription(),
                'publicationDate' => $book->getPublicationDate()->format('Y-m-d'),
                'image' => $book->getImage()
            ];
        }

        return new JsonResponse(['author' => [
            'id' => $author->getId(),
            'firstName' => $author->getFirstName(),
            'middleName' => $author->getMiddleName(),
            'lastName' => $author->getLastName()
        ], 'books' => $booksArray]);
    }
}
