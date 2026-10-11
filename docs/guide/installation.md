# Installation

This development branch requires CakePHP 6 and PHP 8.5. Run these commands
in your application directory:

```sh
composer config minimum-stability dev
composer config prefer-stable true
composer require cakephp/cakephp:^6.0@dev dereuromark/cakephp-comments:dev-cake6
bin/cake plugin load Comments
```

Composer checks the supported PHP and CakePHP versions. See [composer.json](https://github.com/dereuromark/cakephp-comments/blob/cake6/composer.json) for the dependency constraints.

## Create the comments table

If your application does not already have the Migrations plugin, install and load it:

```sh
composer require cakephp/migrations:dev-fix/6.x-cakephp-compatibility
bin/cake plugin load Migrations
```

Migrations currently needs the upstream CakePHP 6 compatibility branch above.
Run the Comments migrations:

```sh
bin/cake migrations migrate -p Comments
```

This creates `comments_comments`. The default connection must point to the same database used by your application. For another connection, add `-c connection_name` to the migration command and configure the Comments table to use that connection.

The examples use an existing `Posts` table and an existing `Users` table with integer user IDs. Comments can belong to other application models, but the plugin does not create your application tables or authentication system. Read [Models and migrations](../reference/models) before migrating if the records receiving comments use UUIDs.

## Continue

Follow [Getting started](./getting-started) to display and submit comments on a post. Admin access is disabled until you configure its [access callback](../reference/admin).
