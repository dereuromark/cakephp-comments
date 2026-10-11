# Installation

Install the plugin in your CakePHP application:

```sh
composer require dereuromark/cakephp-comments
bin/cake plugin load Comments
```

Composer checks the supported PHP and CakePHP versions. See [composer.json](https://github.com/dereuromark/cakephp-comments/blob/master/composer.json) for the dependency constraints.

## Create the comments table

If your application does not already have the Migrations plugin, install and load it:

```sh
composer require cakephp/migrations
bin/cake plugin load Migrations
```

Run the Comments migrations:

```sh
bin/cake migrations migrate -p Comments
```

This creates `comments_comments`. The default connection must point to the same database used by your application. For another connection, add `-c connection_name` to the migration command and configure the Comments table to use that connection.

The examples use an existing `Posts` table and an existing `Users` table with integer user IDs. Comments can belong to other application models, but the plugin does not create your application tables or authentication system. Read [Models and migrations](../reference/models) before migrating if the records receiving comments use UUIDs.

## Continue

Follow [Getting started](./getting-started) to display and submit comments on a post. Admin access is disabled until you configure its [access callback](../reference/admin).
