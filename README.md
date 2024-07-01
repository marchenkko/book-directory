
# Book Directory Project #

## Project Overview ##

What the project does
This project is a Book Management System built using Symfony. It allows users to manage books and authors, providing features such as creating, viewing, and editing books and authors. Users can also search for books by an author's last name and view all books written by a specific author.

## How to run ##

Instructions:

* Clone the repository
git clone <https://github.com/marchenkko/book-directory.git>

* cd "book-directory"

* Install dependencies
composer install

* Update the .env file with your database configuration
DATABASE_URL="mysql://username:password@https://127.0.0.1:8000/database_name"

* Run migrations
php bin/console doctrine:migrations:migrate

* Start the server
 symfony serve

## Postman Collection ##

* https://elements.getpostman.com/redirect?entityId=22328482-b1341f83-d227-445e-bda6-8f11ce09c04a&entityType=collection
