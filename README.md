# allipunkte

Get started:
What you need:

Frontend:

- Node.js

Backend: (Install scripts: [MacOS](setup/brew_install_script.sh) / Windows / Linux)

- PHP 8.1 or higher
- Composer
- Symfony
- Mysql/MariaDB
- (optional) symfony helper software

Installation:

api: Api is the backend server.

1. Run `composer install`
2. Setup local database server for development. (If wanted with [docker compose file](setup/docker-compose.yaml))
3. Configure your database connection in [.env](api/.env) or create a new .env.local
4. Run `php bin/console doctrine:database:create` to create the database
5. Run `php bin/console doctrine:migrations:migrate` and confirm to create the required tables
6. Start the server with `symfony serve` or similar

app: The vue frontend.

1. Install vue/cli globally with `npm -g install @vue/cli`
2. run `npm install`
3. serve with `npm run serve`

Now you should have two servers running, by default on https://localhost:8000 for the backend and https://localhost:8080.
For the system to work on localhost (due to cookie restrictions) be sure to either use http or https on both servers.
Mixing will cause the authentication to succeed, but not set the cookie, driving you insanve
