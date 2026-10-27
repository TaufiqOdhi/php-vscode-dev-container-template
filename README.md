# PHP VS Code Dev Container Template

A local PHP development stack inspired by XAMPP. Run it with Docker Compose on its own or open the same stack as a VS Code Dev Container. Develop multiple projects in `workspace/`; their files stay on your machine, and MariaDB data is kept in a Docker volume.

## What's included

- PHP 8.4 with Apache, `mod_rewrite`, and support for projects with a `public/` document root
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

The home page is a status page and should say **Database connected** once the stack is ready. phpMyAdmin prompts for a username and password; use the values of `DB_USERNAME` and `DB_PASSWORD` in `.env` if you changed them. The MariaDB root password is configured separately with `DB_ROOT_PASSWORD`.

### Open in VS Code

1. Open this folder in VS Code and install the Dev Containers extension.
2. Run **Dev Containers: Reopen in Container** from the Command Palette.
3. Wait for the image to build and VS Code to connect. It opens `workspace/` as `/workspace` in the container. The terminal, PHP language tools, and debugger run inside the `app` container.
4. Add or clone projects inside `workspace/`. Source changes are available immediately through the bind mount.

You can use this workflow without running `docker compose up` first; VS Code starts the Compose services. Closing the Dev Container stops the Compose stack, including services started manually. Database data persists.

Ports are published on the Docker host's loopback interface. If Docker runs on a remote machine, forward its published ports to your local machine using your remote connection's port forwarding.

## Working with projects

Put each project in its own directory under `workspace/`. For example:

```text
workspace/
  my-project/
    composer.json
    public/
      index.php
  another-project/
    public/
      index.php
```

Apache serves these projects at `http://localhost:8080/projects/my-project/public/` and `http://localhost:8080/projects/another-project/public/`. Replace `8080` with your configured `APP_PORT` if necessary. Apache allows access only to each project's `public/` directory; project files such as `composer.json` and `.env` remain outside the web root. `mod_rewrite` and `.htaccess` overrides are enabled in `public/`.

The projects are ignored by this template's Git repository, so each can have its own repository. The shared VS Code debug configuration in `workspace/.vscode/launch.json` is tracked by the template.

Some frameworks need their base URL configured for the `/projects/<name>/public/` path. For a project that must run at `/`, start its own server in the container, for example `php -S 0.0.0.0:8000 -t public` from the project directory, and forward port 8000 using the VS Code Ports tab. Projects without a `public/` directory can use their own server or a custom Apache virtual host in `docker/apache/000-default.conf`.

## Working with PHP and Composer

Run commands in the VS Code container terminal:

```sh
php -v
php -m
cd my-project
composer install
```

Run Composer from the project that owns the `composer.json`; each project manages its own dependencies and lock file. The template's sample `composer.json` and status page remain in the repository outside `workspace/`.

Without VS Code, commands can also be run through Compose:

```sh
docker compose exec --user vscode app php -v
docker compose exec --user vscode -w /workspace/my-project app composer install
```

The image's `vscode` user starts with UID/GID 1000. On Linux, VS Code updates that user to match the local user's UID/GID when opening the Dev Container. If you use Compose alone and your UID differs, run write-producing commands with your UID and GID instead, for example `docker compose exec --user "$(id -u):$(id -g)" -e HOME=/tmp -w /workspace/my-project app composer install` in a Unix shell.

## Database access

Inside any Compose service, connect to `db` on port `3306`; do not use `localhost` or the host port from another container. The application receives `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` as environment variables. The sample page demonstrates a PDO connection.

For a MariaDB shell with the default database user:

```sh
docker compose exec db mariadb -u app -p app
```

Enter `app_password` when prompted. Substitute your configured database and username if you changed them.

MariaDB uses the database name, user, and passwords in `.env` **only when the data volume is first initialized**. Changing those values later does not change existing database accounts or databases; update them in MariaDB as well, or reset the volume if you no longer need its data. To reset it, stop the stack and delete its named volumes with `docker compose down -v`, then start it again. This permanently deletes the stored database data.

## Debug with Xdebug

1. Open the folder in the Dev Container and set a breakpoint in `my-project/public/index.php`.
2. In **Run and Debug**, start **Listen for Xdebug**.
3. Visit http://localhost:8080/projects/my-project/public/?XDEBUG_TRIGGER=1, using your project name and configured application port. The request stops at the breakpoint.

The debug adapter and PHP run in the same container, so Xdebug connects to `127.0.0.1:9003` inside it. There is no need to publish that port. To debug a CLI script, start the listener and run `XDEBUG_TRIGGER=1 php path/to/script.php` in the container terminal. Set `XDEBUG_MODE=off` in `.env` and recreate the app container to disable Xdebug; the default is `debug,develop`.

## Configuration and lifecycle

Edit `.env` to change the host ports or development credentials, then apply the changes with `docker compose up -d`. For a fresh database, choose your credentials before the first start. The PHP image, extensions, and Apache/PHP settings are defined in `Dockerfile` and `docker/`; after changing them, rebuild with `docker compose up -d --build` or **Dev Containers: Rebuild Container**.

```sh
docker compose ps                # service status
docker compose logs -f app db    # application and database logs
docker compose down              # stop and remove containers; keep database data
```

If a port is already in use, change `APP_PORT`, `PHPMYADMIN_PORT`, or `DB_HOST_PORT` in `.env` and run `docker compose up -d` again. If the database fails to become healthy, inspect `docker compose logs db`. If the home page cannot connect after credentials were changed, check the existing-volume behavior described above.

### Dev Container build permission error

Some Docker Compose and Buildx versions reject the temporary Dockerfile generated by VS Code with an `additional privileges requested: --allow=fs.read` error ([upstream issue](https://github.com/docker/compose/issues/14285)). The Dev Container Compose override grants build-time read access to the host temporary directory to work around this; the normal `docker compose` configuration is unchanged, and Buildx's filesystem checks remain enabled. The temporary directory is not mounted into the running container.

The override follows `TMPDIR`, `TMP`, or `TEMP` (falling back to `/tmp`). To limit access to the Dev Containers CLI directory instead, set `DEVCONTAINER_CLI_TMPDIR` in `.env` to that directory; on Linux or WSL it is typically `/tmp/devcontainercli-<your-username>`. If you use a custom temporary directory, use the directory shown before `/container-features/` in the error message. Then run **Dev Containers: Rebuild and Reopen in Container**, or **Dev Containers: Rebuild Container** if already connected.
