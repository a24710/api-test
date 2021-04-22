# Agile Monkeys API Test
### Setup guide

**Prerequisites**: Make sure that docker and docker-compose are installed in your machine and available for your console. Same with openssl command.

Download the repository code to a folder in your machine

Open a console and generate your public and private keys with the commands (it will ask you a pass phrase)

    openssl genpkey -algorithm RSA -aes256 -out private.pem
    openssl rsa -in private.pem -pubout -outform PEM -out public.pem

Move these private.pem and public.pem in the folder "config/jwt"

Create a new file in the project root directory called .env.local (including dots)

Insert a new line and replace PASSPHRASE with the one you introduced before. Don't introduce blanks, just type it as show here.

    JWT_PASSWORD="PASSPHRASE"

Save it and go back to the root project directory and setup all the needed docker containers running the command

    docker-compose up --build

All containers should be up and running now.

In a new console open a bash in the recently created php container with

    docker exec -it agile_php_cont bash

Run composer install within this bash (composer was installed during the docker php container setup)

    composer install

Update the database schema with

    php bin/console d:s:u --force

Load the data fixtures with

    php bin/console doctrine:fixtures:load

Create the database for the test environment and populate it with fixture data

    php bin/console doctrine:database:create --env=test
    php bin/console d:s:u --force --env=test
    php bin/console doctrine:fixtures:load --env=test

You can launch the provided tests with (It will install php unit the first time)

    php bin/phpunit

In order to upload customer avatars you need to take ownership of the directory public/avatars. 

Exit the docker php container and run: (replace 'youruser' with your user name)

    sudo chown youruser public/avatars

A postman collection is available in the root directory to test all api methods.
Please, notice that uuids are randomly generated, and the ones present in some postman urls are just placeholders.
In order to test this urls replace the placeholder uuid with a real one you get from a get collection call.

If you want a deeper explanation check the "README extended.pdf" file in the root folder.

