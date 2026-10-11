# Models and migrations

## Comment storage

The plugin stores comments in `comments_comments`. Each comment has a `model` table alias and a `foreign_key` identifying the target row. `user_id` is nullable for guests, and `parent_id` can identify another comment on the same target.

The default migration also creates optional `name`, `email`, and `title` columns, required `content`, boolean `is_private` and `is_spam` flags, and `created` and `modified` timestamps. It creates no database foreign keys to your application tables. ORM rules check the Users and parent-comment associations when saving.

The entity allows public assignment of name, email, title, and content. Identity, target, parent, and moderation columns require trusted server code. `CommentsTable::add()` accepts server-trusted relation data; do not pass an unfiltered request body to it directly.

## Attach the behavior

In an application table's `initialize()` method:

```php
$this->addBehavior('Comments.Commentable');
```

This adds a `Comments` association restricted to that table alias. The association orders comments by creation time. Load comments with a normal contain:

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

The behavior's `commentAdd($parentId, $options)` validates a parent against the target model and record ID. The component accepts `parent_id`; the separate public controller always creates a top-level comment. The component displays a flat list. You can build a threaded presentation yourself from the parent relation.

`countComments` attaches CounterCache and requires a `comments_count` column on your application table. Add that column with an application migration before enabling it. For multiple commentable table types, review and scope the counter associations to your application's schema.
