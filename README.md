# PHP VS Code Dev Container Template

A local PHP development stack inspired by XAMPP. Run it with Docker Compose on its own or open the same stack as a VS Code Dev Container. Your project files stay on your machine; MariaDB data is kept in a Docker volume.

## What's included

- PHP 8.4 with Apache, `mod_rewrite`, and a `public/` document root
- Composer 2 and common PHP extensions: PDO MySQL, MySQLi, GD, Intl, Mbstring, Zip, BCMath, EXIF, and OPcache
- MariaDB 11.4 and phpMyAdmin
- Xdebug and a VS Code debug configuration
- A small PHP page that checks the database connection

This is a **local development environment**: it enables PHP error display, has example passwords, and binds published ports to localhost. Configure a separate production environment for deployment.

## Prerequisites

- Docker Engine or Docker Desktop with the Docker Compose plugin, running on your machine
- For the editor workflow: [VS Code](https://code.visualstudio.com/) and the [Dev Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers)

## Get started

From this directory, create a local configuration file. The stack also works without `.env` using the defaults below, but copying it makes the settings easy to change.

```sh
cp .env.example .env
docker compose up -d --build
```

In PowerShell, use `Copy-Item .env.example .env` instead of `cp` if needed. The first build downloads the images and compiles PHP extensions, so it may take a few minutes.

Open these addresses on the machine running Docker:

| Service | Address | Default credentials |
| --- | --- | --- |
| PHP application | http://localhost:8080 | None |
| phpMyAdmin | http://localhost:8081 | `app` / `app_password` |
| MariaDB from a host SQL client | `127.0.0.1:3307` | `app` / `app_password`, database `app` |

The home page should say **Database connected** once the stack is ready. phpMyAdmin prompts for a username and password; use the values of `DB_USERNAME` and `DB_PASSWORD` in `.env` if you changed them. The MariaDB root password is configured separately with `DB_ROOT_PASSWORD`.

### Open in VS Code

1. Open this folder in VS Code and install the Dev Containers extension.
2. Run **Dev Containers: Reopen in Container** from the Command Palette.
3. Wait for the image to build and VS Code to connect. The terminal, PHP language tools, and debugger now run inside the `app` container.
4. Edit `public/index.php` and refresh http://localhost:8080. Source changes are available immediately through the bind mount.

You can use this workflow without running `docker compose up` first; VS Code starts the Compose services. Closing the Dev Container stops the Compose stack, including services started manually. Database data persists.

Ports are published on the Docker host's loopback interface. If Docker runs on a remote machine, forward its published ports to your local machine using your remote connection's port forwarding.

## Working with PHP and Composer

Run commands in the VS Code container terminal:

```sh
php -v
php -m
composer install
composer lint
```

The starter `composer.json` contains only the PHP and database extension requirements; add your project dependencies as needed. `composer install` uses the committed lock file and creates a `vendor/` directory, which is ignored by Git. Commit changes to `composer.lock` when you add or update dependencies.

Without VS Code, commands can also be run through Compose:

```sh
docker compose exec --user vscode app php -v
docker compose exec --user vscode app composer install
```

The image's `vscode` user starts with UID/GID 1000. On Linux, VS Code updates that user to match the local user's UID/GID when opening the Dev Container. If you use Compose alone and your UID differs, run write-producing commands with your UID and GID instead, for example `docker compose exec --user "$(id -u):$(id -g)" -e HOME=/tmp app composer install` in a Unix shell.

Apache serves only files inside `public/`, leaving Composer files, `.env`, and other project files outside the web root. `mod_rewrite` is enabled and `.htaccess` overrides are allowed. Add application code and your own routing there, or change the virtual host in `docker/apache/000-default.conf` for your framework.

## Database access

Inside any Compose service, connect to `db` on port `3306`; do not use `localhost` or the host port from another container. The application receives `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` as environment variables. The sample page demonstrates a PDO connection.

For a MariaDB shell with the default database user:

```sh
docker compose exec db mariadb -u app -p app
```

Enter `app_password` when prompted. Substitute your configured database and username if you changed them.

MariaDB uses the database name, user, and passwords in `.env` **only when the data volume is first initialized**. Changing those values later does not change existing database accounts or databases; update them in MariaDB as well, or reset the volume if you no longer need its data. To reset it, stop the stack and delete its named volumes with `docker compose down -v`, then start it again. This permanently deletes the stored database data.

## Debug with Xdebug

1. Open the folder in the Dev Container and set a breakpoint in `public/index.php`.
2. In **Run and Debug**, start **Listen for Xdebug**.
3. Visit http://localhost:8080/?XDEBUG_TRIGGER=1, using your configured application port if different. The request stops at the breakpoint.

The debug adapter and PHP run in the same container, so Xdebug connects to `127.0.0.1:9003` inside it. There is no need to publish that port. To debug a CLI script, start the listener and run `XDEBUG_TRIGGER=1 php path/to/script.php` in the container terminal. Set `XDEBUG_MODE=off` in `.env` and recreate the app container to disable Xdebug; the default is `debug,develop`.

## Configuration and lifecycle

Edit `.env` to change the host ports or development credentials, then apply the changes with `docker compose up -d`. For a fresh database, choose your credentials before the first start. The PHP image, extensions, and Apache/PHP settings are defined in `Dockerfile` and `docker/`; after changing them, rebuild with `docker compose up -d --build` or **Dev Containers: Rebuild Container**.

```sh
docker compose ps                # service status
docker compose logs -f app db    # application and database logs
docker compose down              # stop and remove containers; keep database data
```

If a port is already in use, change `APP_PORT`, `PHPMYADMIN_PORT`, or `DB_HOST_PORT` in `.env` and run `docker compose up -d` again. If the database fails to become healthy, inspect `docker compose logs db`. If the home page cannot connect after credentials were changed, check the existing-volume behavior described above.
