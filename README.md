# mobilecombos.com backend API

[![codecov](https://codecov.io/gh/mobilecombos/backend-api/branch/master/graph/badge.svg?token=iQubs8aOkQ)](https://codecov.io/gh/mobilecombos/backend-api)

This is the backend API for mobilecombos.com. This works in unison with the [standalone frontend](https://github.com/mobilecombos/website-frontend) to fully implement the website's features.

## Local development

This project is written in PHP with Laravel 10.

To run this locally, you should use Laravel Sail and Composer.

> If you're using VS Code, you might want to use the included dev container. You should be prompted automatically by VS Code to relaunch the project in the provided dev container.

### Requirements

-   Docker
-   PHP 8.1 locally
-   Composer 2 or later
-   Linux (including via WSL)

### Installing dependencies

```
composer update -W
```

This will install all the required PHP dependencies for the project.

### Environment setup

Add the following line to your `~/.bashrc` or `~/.zshrc`:

```
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'
```

Then, run `source ~/.bashrc` to update your active terminal instance.

### Laravel environment

Make a copy of `.env.example` named `.env`.

By default, the app will run at `http://local.davw.network`. This is a publicly available domain which resolves to localhost (`127.0.0.1`).

You can change this by altering the `.env` key-value pairs `APP_URL` and `SESSION_DOMAIN`.

By default, this `.env` file will have an empty app key. You should never share this app key as it is used to generate a secret used for encryption within your Laravel app. When you launch the Sail containers, you will be prompted to generate an app key.

### Running the API

Now, simply tell Sail to boot all the containers.

```
sail up -d
```

This might take several minutes depending on your device and internet speed. It will pull many Docker images and install various OS dependencies within its Docker containers needed to run the app.

The majority of this is a one-time process. Subsequent runs will take only a matter of seconds to start.

Once you are told that all containers are started, navigate to http://local.davw.network. You will be prompted with an error page, with an option to generate an app key. Click this, and your setup is complete.

### Migrations

The one remaining step is to run all migrations. This sets up the database structure as required.

```
sail artisan migrate
```

### Database seeding

The app contains some basic sample data which you can fill the database with. This contains various devices, modems and combo sets.

This provides some basic data to test the API with.

```
sail artisan db:seed
```

### Run tests

The API includes various basic tests, with more being added throughout development.

You can use `sail` to run these, provided the Docker containers are running.

```
sail up -d
sail artisan db:reset
sail artisan migrate

sail artisan test
```

## Using the API

The API is available under the `v1` suffix, in order to allow for future breaking changes, and backwards compatibility.

The API complies with the JSON:API standard, exposing models as resources, and using the `filter`, `sort` and `include` query parameters.

For example, to list all devices, including their modems, you can perform a GET request against `/v1/api/devices?include=modem&sort=-releaseDate`.
