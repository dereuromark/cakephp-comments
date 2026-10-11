# Access and identity

## Signed-in users

The plugin reads the request's `identity` attribute first. It uses `userIdField` when the identity supports `ArrayAccess`, otherwise it calls `getIdentifier()` when available. The identifier must match a `Users` record because the Comments table checks the user association before saving.

If no request identity is available, it checks an available TinyAuth `AuthUser` component, then a legacy `Auth` component, and finally the configured session path. This fallback supports older integrations; it is not an authentication system.

For a session containing the user at `Auth` rather than `Auth.User`, add this to `config/app.php`:

```php
'Comments' => [
    'sessionKey' => 'Auth',
    'userIdField' => 'id',
    'controllerModels' => ['Posts' => 'Posts'],
],
```

The default session path is `Auth.User.id`. Modern Authentication integrations usually need no session setting because the request identity is read directly. A session key depends on your application's authenticator configuration.

## Anonymous comments

Enable guest submissions explicitly if your application permits them:

```php
'Comments' => [
    'allowAnonymous' => true,
    'controllerModels' => ['Posts' => 'Posts'],
],
```

This permits guest creation through both the public controller and the component. It does not bypass authentication middleware that blocks the request first. Guest `user_id` is null; `name` and `email` are optional input fields. The default Users association still exists, so this setting does not remove your application's Users table requirement for the supplied admin pages.

## Record access

`controllerModels` selects which tables may receive comments. It does not check whether a caller may read or comment on a particular row. Enforce that rule in your application before allowing requests through to the plugin controller. The same applies to the application action using the component.

Keep CSRF, form protection, and application access checks enabled. Escape user content before rendering. Add rate limits or spam controls in your application if you need them; the plugin does not include a complete spam prevention service.

## Deletion

The public delete action accepts POST or DELETE. The signed-in owner can delete their comment. Other callers need the `Comments.adminAccess` callback to return literal `true`. Guests cannot prove ownership through a name or email.

The separate public add action ignores submitted identity, target, and parent columns. The component also replaces identity and target columns with its configured server data and validates the supplied parent against both the model and record ID.

## Admin access

See [Admin](../reference/admin) for the required callback. The plugin inherits the application's controller behavior. It has no standalone mode that bypasses authentication or authorization.
