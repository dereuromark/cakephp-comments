# Getting started

Complete [Installation](./installation) first. This example adds a comment form to an existing post page using the plugin's separate controller. Your application already needs `Posts`, `Users`, and a `PostsController::view()` action.

## Allow comments on posts

Add this configuration to the array returned by `config/app.php`:

```php
'Comments' => [
    'controllerModels' => [
        'Posts' => 'Posts',
    ],
],
```

The key is the public URL alias. The value is the table class name to load. Only configured aliases can receive comments through the plugin controller.

Anonymous comments are disabled by default. A signed-in request must have an identity whose identifier matches a record in `Users`. See [Access and identity](./access) if your application needs guest comments or uses a legacy session login.

## Load the helper

In `src/View/AppView.php`, load the helper inside `initialize()` alongside your existing helpers:

```php
$this->loadHelper('Comments.Comments');
```

## Display the comments

Attach the behavior in `src/Model/Table/PostsTable.php`, inside its existing `initialize()` method:

```php
$this->addBehavior('Comments.Commentable');
```

In your existing `PostsController::view()` action, load the post and its comments:

```php
$post = $this->fetchTable('Posts')->get($id, contain: ['Comments']);
$this->set(compact('post'));
```

Add this to `templates/Posts/view.php`:

```php
<?php foreach ($post->comments as $comment): ?>
    <p><?= nl2br(h($comment->content)) ?></p>
<?php endforeach; ?>

<?= $this->Form->create(null, ['url' => $this->Comments->url('Posts', $post->id)]) ?>
<?= $this->Form->control('content', ['type' => 'textarea', 'required' => true]) ?>
<?= $this->Form->button(__('Add comment')) ?>
<?= $this->Form->end() ?>
```

The plugin uses your application's `AppController`, layout, Flash component, authentication, and middleware. Permit the public `CommentsController::add()` action for the users who may comment, while retaining CSRF and form protection. Render flash messages in your layout if it does not already do so:

```php
<?= $this->Flash->render() ?>
```

## Check the result

Open an existing post while signed in, submit a nonempty comment, and reload the page. The new comment should appear on that post. Submission redirects to the referring page; without a local referer it falls back to the application home page.

An empty comment is rejected with an error flash message. A signed-out submission receives a 403 unless anonymous comments are enabled. Escape comment content when rendering it, as the template above does.

The plugin stores `is_private` and `is_spam` flags, but these flags do not automatically hide comments in this basic query. Add your visibility policy to the query before using moderation flags in production. See [Models and migrations](../reference/models#visibility).

If you prefer posting to the same application action, use the [Comment component](./component) instead.
