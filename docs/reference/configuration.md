# Configuration

Add `Comments` settings to the array returned by `config/app.php`, or write them through `Cake\Core\Configure`. The example file at [config/app.example.php](https://github.com/dereuromark/cakephp-comments/blob/cake6/config/app.example.php) is a starting point; the plugin does not load it automatically.

## Public controller and access

| Setting | Default | Effect |
| --- | --- | --- |
| `controllerModels` | Empty | Allowed URL aliases mapped to table class names. |
| `allowAnonymous` | `false` | Permit comment creation without a user ID. |
| `userIdField` | `id` | Field read from an array-like identity, auth component, or session. |
| `sessionKey` | `Auth.User` | Session path used only without a loaded auth component, before the user ID field. |
| `adminAccess` | Unset | Closure receiving the request; literal `true` grants admin and elevated delete access. |

## Component

Per-component configuration overrides the global `Comments` settings:

```php
$this->loadComponent('Comments.Comment', [
    'actions' => ['view'],
    'viewVariable' => 'post',
    'allowAnonymous' => false,
]);
```

| Setting | Default | Effect |
| --- | --- | --- |
| `actions` | Unset | Process every action when unset; otherwise limit to the named actions. |
| `on` | `beforeRender` | Process submitted comment data during this callback; `startup` is also supported. |
| `viewVariable` | Derived entity name | View variable holding the entity being commented on. |
| `useEntity` | `true` | Take the target ID from the view entity rather than submitted `id`. |
| `userId` | Unset | Explicit server-supplied user ID, before identity and session lookup. |
| `userModelClass` | `Users` | User model name passed when attaching the behavior. |

## Behavior

| Setting | Default | Effect |
| --- | --- | --- |
| `commentClass` | `Comments.Comments` | Comments table class used for the association. |
| `model` | Parent table alias | Exact stored identifier for reads, component/public-controller writes and replies. `null` resolves to the table alias. |
| `modelClass` | Parent table alias | Back-association to the parent table on the Comments table. |
| `userModelAlias` | `Users` | User association to add if absent. |
| `userModelClass` | `Users` | Class for a newly added user association. |
| `userModel` | Unset | Array of association options for a newly added user association. |
| `countComments` | `false` | Attach CounterCache to the Comments table. Requires a parent counter column. |
| `fieldCounter` | `comments_count` | Parent column used with `countComments`. The migration does not create it. |
| `hiddenField` | Unset | Optional visibility condition in the `comments` finder. Does not affect other queries. |

Set `model` on each behavior when tables need different identifiers. A global `Comments.model` supplies the same default to behavior initialization and the public controller when no behavior is attached. Use that global value only for a single comment target. For multiple tables, configure distinct identifiers on each behavior; sharing one identifier also shares comments with the same record ID and dependent deletion scope. A behavior-specific value takes precedence. Attach or configure the behavior before processing comments; changing its setting after initialization does not rebuild association conditions.

The supplied Comments table already has a `Users` association and its save rule uses that association. Setting a different `userModelClass` alone does not replace it. Customize the Comments table and its associations if your application uses a different user model.

Historical options such as `approval`, `threaded`, `titleField`, `spamField`, `nameField`, and `emailField` do not implement the behavior suggested by their names. In particular, `emailField => false` does not enforce signed-in commenting, and `userModelClass => false` does not enable a guest-only mode. Use `allowAnonymous` for guest access and implement visibility rules explicitly.
