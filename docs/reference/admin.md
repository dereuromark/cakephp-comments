# Admin

The plugin includes list, view, edit, and delete actions at `/admin/comments`. Its templates use CakePHP helpers and your application's layout. No Tools or icon plugin is required.

## Configure access

Admin access is denied until `Comments.adminAccess` is a Closure returning literal `true` for permitted requests. Add a callback to your application configuration. Adapt the example to your identity and role model:

```php
use Cake\Core\Configure;
use Cake\Http\ServerRequest;

Configure::write('Comments.adminAccess', function (ServerRequest $request): bool {
    $identity = $request->getAttribute('identity');

    return $identity !== null && in_array('admin', (array)$identity->get('roles'), true);
});
```

This example assumes your identity exposes `get('roles')`. Use the methods supported by your own identity implementation. The same callback permits deleting another user's comment through the public delete action.

The plugin inherits `AppController`, including its authentication and authorization behavior. The host's `beforeFilter()` returns `void`; set a redirect response on the event with `$event->setResult($this->redirect('/login'))`. The plugin preserves that response. When the request has an Authorization service, the plugin marks its explicit callback check for that request. This does not undo a denial or redirect that already occurred in the application.

An authentication check running during `Controller.startup` occurs after the admin
access callback. A callback denying a signed-out request can therefore produce a
403 before a later authentication redirect. Set a signed-out redirect response
on the event in your host `beforeFilter()` if that is your intended behavior.

False results, unset callbacks, and unexpected callback failures deny access. Explicit HTTP exceptions raised by the callback retain their status. Unexpected failures are logged without their exception message.

## Routes

The plugin registers its routes when loaded normally. You do not need to add a second copy. If you disable plugin routes to manage them yourself, include the admin index and fallbacks:

```php
use Cake\Routing\RouteBuilder;

$routes->prefix('Admin', function (RouteBuilder $routes): void {
    $routes->plugin('Comments', function (RouteBuilder $routes): void {
        $routes->connect('/', ['controller' => 'Comments', 'action' => 'index']);
        $routes->fallbacks();
    });
});
```

This example defines the admin routes only. The public helper also needs the public plugin routes when you use the separate controller.

## Editing

Admins can change comment text, guest details, and private/spam flags. The target model and record ID remain read-only. The flags do not automatically filter public queries; see [Visibility](./models.md#visibility).

Delete actions require POST or DELETE. Comments with replies are retained until their replies have been deleted. Keep your application's CSRF and form protection enabled. The templates use your layout and optional CSS classes; supply application styling as needed.
