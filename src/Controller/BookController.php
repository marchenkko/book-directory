<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Author;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{
    #[Route('/books', name: 'book_list')]
    public function bookList(EntityManagerInterface $em): Response
    {
        $books = $em->getRepository(Book::class)->findAll();

        return $this->render('book/list.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/books/create', name: 'book_create_form')]
    public function createBookForm(Request $request, EntityManagerInterface $em): Response
    {
        $error = $request->query->get('error');
        $authors = $em->getRepository(Author::class)->findAll();
        return $this->render('book/create.html.twig', [
            'authors' => $authors,
            'error' => $error
        ]);
    }

    #[Route('/books/create/post', name: 'book_create', methods: ['POST'])]
    public function createBook(Request $request, EntityManagerInterface $em): Response
    {
        $book = new Book();
        $book->setTitle($request->get('title'));
        $book->setShortDescription($request->get('shortDescription'));
        $book->setPublicationDate(new \DateTime($request->get('publicationDate')));

        $authors = $request->get('authors');
        foreach ($authors as $authorId) {
            $author = $em->getRepository(Author::class)->find($authorId);
            if ($author) {
                $book->addAuthor($author);
            }
        }

        $image = $request->files->get('image');
        if ($image) {
            $filename = md5(uniqid()) . '.' . $image->guessExtension();

            try {
                $image->move(
                    $this->getParameter('images_directory'),
                    $filename
                );
                $book->setImage($filename);
            } catch (\Exception $e) {
                return $this->redirectToRoute('book_create_form', ['error' => 'Could not upload image']);
            }
        }

        try {
            $em->persist($book);
            $em->flush();
        } catch (\Exception $e) {
            return $this->redirectToRoute('book_create_form', ['error' => 'Failed to create book']);
        }

        return $this->redirectToRoute('home');
    }

    #[Route('/books/search', name: 'book_search_form')]
    public function searchBookForm(): Response
    {
        return $this->render('book/search.html.twig');
    }

    #[Route('/books/search/post', name: 'book_search', methods: ['POST'])]
    public function searchBook(Request $request, EntityManagerInterface $em): Response
    {
        $lastName = $request->request->get('lastName');
        $author = $em->getRepository(Author::class)->findOneBy(['lastName' => $lastName]);

        if (!$author) {
            return $this->render('book/search.html.twig', [
                'error' => 'No authors found with this last name.',
            ]);
        }

        return $this->redirectToRoute('books_by_author', ['id' => $author->getId()]);
    }

    #[Route('/books/author/{id}', name: 'books_by_author')]
    public function showBooksByAuthor($id, EntityManagerInterface $em): Response
    {
        $author = $em->getRepository(Author::class)->find($id);

        if (!$author) {
            throw $this->createNotFoundException('The author does not exist');
        }

        return $this->render('book/books_by_author.html.twig', [
            'author' => $author,
            'books' => $author->getBooks(),
        ]);
    }

    #[Route('/books/show', name: 'book_show_form')]
    public function showBookForm(): Response
    {
        return $this->render('book/show_form.html.twig');
    }

    #[Route('/books/showById', name: 'book_show', methods: ['get'])]
    public function showBook(Request $request, EntityManagerInterface $em): Response
    {
        $id = $request->get('id');
        $book = $em->getRepository(Book::class)->find($id);

        if (!$book) {
            throw $this->createNotFoundException('The book does not exist');
        }

        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/books/edit', name: 'book_edit_list')]
    public function editBookList(EntityManagerInterface $em): Response
    {
        $books = $em->getRepository(Book::class)->findAll();
        return $this->render('book/edit_list.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/books/{id}/edit', name: 'book_edit_form')]
    public function editBookForm($id, EntityManagerInterface $em, Request $request): Response
    {
        $book = $em->getRepository(Book::class)->find($id);
        $authors = $em->getRepository(Author::class)->findAll();

        if (!$book) {
            throw $this->createNotFoundException('The book does not exist');
        }

        $error = $request->query->get('error');

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'authors' => $authors,
            'error' => $error,
        ]);
    }

    #[Route('/books/{id}/edit/post', name: 'book_edit', methods: ['POST'])]
    public function editBook(Request $request, $id, EntityManagerInterface $em): Response
    {
        $book = $em->getRepository(Book::class)->find($id);

        if (!$book) {
            return new JsonResponse(['error' => 'The book does not exist'], Response::HTTP_NOT_FOUND);
        }

        $book->setTitle($request->get('title'));
        $book->setShortDescription($request->get('shortDescription'));
        $book->setPublicationDate(new \DateTime($request->get('publicationDate')));

        $authors = $request->get('authors');
        if (is_array($authors)) {
            $book->clearAuthors();
            foreach ($authors as $authorId) {
                $author = $em->getRepository(Author::class)->find($authorId);
                if ($author) {
                    $book->addAuthor($author);
                }
            }
        }

        $image = $request->files->get('image');
        if ($image) {
            $filename = md5(uniqid()) . '.' . $image->guessExtension();

            try {
                $image->move(
                    $this->getParameter('images_directory'),
                    $filename
                );
                $book->setImage($filename);
            } catch (\Exception $e) {
                return $this->redirectToRoute('book_edit_form', ['id' => $id, 'error' => 'Could not upload image']);
            }
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            return $this->redirectToRoute('book_edit_form', ['id' => $id, 'error' => 'Failed to update book']);
        }

        return $this->redirectToRoute('home');
    }
}
