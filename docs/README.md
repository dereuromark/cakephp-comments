# Comments Plugin docs

## Strategies
There are different main strategies:

- **Action**: Posting to the same action as the comments are displayed, e.g. a specific entity view
- **Controller**: Posting to the plugin Comments controller with a redirect back to the referer (current view)

Each of those can also be done using AJAX instead of normal PRG.

### Action

Preferred way, directly posting to the same action.
It uses the beforeRender() callback as that one has already the main entity loaded in the view vars.



### Controller

This can be needed, if you cannot post to the same action due to a conflict, e.g.
PRG redirect pattern interfering, or another `request->is('post')` check in that action already.

Make sure to set ACL for this controller if only logged in people are allowed to comment.

#### Configuration

You need to whitelist which models are allowed to receive comments via the controller.
Add this to your `config/app.php`:

```php
'Comments' => [
    'controllerModels' => [
        'Posts' => 'Posts',              // Alias => ModelClass
        'Articles' => 'Blog.Articles',   // Can reference plugin models
    ],
],
```

The key is the alias used in URLs and the helper, the value is the model class to load.

#### Usage

The `Comments.Comments` helper has a `url()` method to generate the form action URL:

```php
// In your template, load the helper
$this->loadHelper('Comments.Comments');

// Generate the URL for a specific entity
$url = $this->Comments->url('Posts', $post->id);
// Returns: /comments/comments/add/Posts/{id}

// Use in a form
echo $this->Form->create(null, ['url' => $this->Comments->url('Posts', $post->id)]);
echo $this->Form->control('comment'); // or 'content' - both are accepted
echo $this->Form->button(__('Submit'));
echo $this->Form->end();
```

The alias passed to `url()` must match a key in your `controllerModels` configuration.

The form field can be named either `comment` or `content` - both are accepted.

### Session Configuration (CakeDC/Users, Authentication Plugin)

By default, the plugin looks for user identity under `Auth.User` session key (legacy CakePHP Auth).
If you're using CakeDC/Users or the modern CakePHP Authentication plugin, the identity is stored under `Auth` directly.

Configure the session key in your `config/app.php`:

```php
'Comments' => [
    'sessionKey' => 'Auth', // For CakeDC/Users or Authentication plugin
    // 'sessionKey' => 'Auth.User', // Default (legacy Auth)
    'userIdField' => 'id', // The field in the user identity containing the user ID
    'controllerModels' => [
        // ...
    ],
],
```

If using the CommentComponent, you can also configure it per-controller:

```php
$this->loadComponent('Comments.Comment', [
    'sessionKey' => 'Auth',
]);
```

## Admin Backend
Go to `/admin/comments`.

Make sure you set up ACL to only have admins access this part.
