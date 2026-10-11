# Models and migrations

## Comment storage

The plugin stores comments in `comments_comments`. Each comment has a stored `model` identifier and a `foreign_key` identifying the target row. `user_id` is nullable for guests, and `parent_id` can identify another comment on the same target.

The default migration also creates optional `name`, `email`, and `title` columns, required `content`, boolean `is_private` and `is_spam` flags, and `created` and `modified` timestamps. It creates no database foreign keys to your application tables. ORM rules check the Users and parent-comment associations when saving.

The entity allows public assignment of name, email, title, and content. Identity, target, parent, and moderation columns require trusted server code. `CommentsTable::add()` accepts server-trusted relation data; do not pass an unfiltered request body to it directly.

## Attach the behavior

In an application table's `initialize()` method:

```php
$this->addBehavior('Comments.Commentable');
```

This adds a `Comments` association restricted to the table alias by equality. That alias is also used for component and public-controller submissions. Comments are ordered by creation time.

For existing comments stored under a different identifier, configure that exact value when attaching the behavior:

```php
$this->addBehavior('Comments.Commentable', ['model' => 'Blog.Articles']);
```

The association, component, public controller and reply validation then use `Blog.Articles`. The locator key or physical table name does not expand the match. Different table aliases stay separate unless you explicitly configure the same stored identifier.

Choose one identifier for each comment target. If both `Articles` and `Blog.Articles` already exist, review the rows and migrate them to the chosen identifier, including parent/reply rows, before expecting one thread. The plugin does not combine mixed identifiers automatically.

Load comments with a normal contain:

```php
$post = $this->fetchTable('Posts')->get($id, contain: ['Comments']);
```

The `comments` finder also contains the user association:

```php
$post = $this->fetchTable('Posts')->find('comments', id: $id)
    ->where(['Posts.id' => $id])
    ->firstOrFail();
```

The `id` finder option restricts the contained comments; it does not restrict the parent Posts query. Always add the parent condition when retrieving a specific record.

## Visibility

Neither the default association nor the component automatically excludes private or spam comments. Apply both filters when your policy requires them:

```php
$post = $this->fetchTable('Posts')->get($id, contain: [
    'Comments' => function (\Cake\ORM\Query\SelectQuery $query): \Cake\ORM\Query\SelectQuery {
        return $query->where([
            'Comments.is_private' => false,
            'Comments.is_spam' => false,
        ]);
    },
]);
```

The admin can edit these flags, but the plugin does not include an approval workflow that changes visibility automatically.

## Primary key types

The `Polymorphic.type` configuration controls `foreign_key` when creating the table. Supported migration types are `integer`, `biginteger`, `uuid`, and `binaryuuid`. For UUID target records, add this to `config/app.php` before the first migration:

```php
'Polymorphic' => [
    'type' => 'uuid',
],
```

This only affects fresh installations. Changing an existing column requires an application migration and a data conversion plan.

`user_id` and `parent_id` remain integer columns. Integer signedness follows `Migrations.unsigned_primary_keys`, which defaults to false. Your Users primary key must be compatible with `user_id`. Custom UUID target keys do not make user IDs or comment IDs UUIDs.

## Replies and counters

The behavior's `commentAdd($parentId, $options)` validates a parent against the target model and record ID. The component accepts `parent_id`; the separate public controller always creates a top-level comment. The component displays a flat list. You can build a threaded presentation yourself from the parent relation. The plugin does not attach Tree or require nested-set columns on Comments when your application table has its own `parent_id` column.

ORM deletion rejects a comment while it still has replies. Remove replies first, then the parent. This rule applies to the public and admin controllers; bulk database deletes bypass ORM rules.

`countComments` attaches CounterCache and requires a `comments_count` column on your application table. Add that column with an application migration before enabling it. For multiple commentable table types, review and scope the counter associations to your application's schema.
