# Comment component

The component handles comments submitted to the same application action that displays the record. Use this when you want to keep the form and its redirect on your own controller.

## Load it for a view action

In your controller's existing `initialize()` method:

```php
$this->loadComponent('Comments.Comment', [
    'actions' => ['view'],
]);
```

Load the entity and set it as a view variable in your `view()` action:

```php
$post = $this->fetchTable('Posts')->get($id);
$this->set(compact('post'));
```

The component attaches `Comments.Commentable` to the controller's default table during startup. The default view variable is the singular entity class name, such as `post`. Use `viewVariable` when your action uses another variable name:

```php
$this->loadComponent('Comments.Comment', [
    'actions' => ['view'],
    'viewVariable' => 'article',
]);
```

Your action must expose the entity before rendering. The component processes submissions during `beforeRender` by default. Configuring `on` to `startup` requires the entity to be available earlier, before the action runs.

## Render a form and list

In the view template:

```php
<?= $this->Form->create(null) ?>
<?= $this->Form->control('content', ['type' => 'textarea']) ?>
<?= $this->Form->button(__('Add comment')) ?>
<?= $this->Form->end() ?>

<?php foreach ($commentsData as $comment): ?>
    <p><?= nl2br(h($comment->content)) ?></p>
<?php endforeach; ?>
<?= $this->Paginator->numbers() ?>
```

Only POST, PUT, and PATCH requests can create a comment. Successful submissions redirect to the current route, including its passed arguments. The component accepts `comment` or `content` as the form field. It enforces `allowAnonymous` and takes the target ID from the entity by default. Keep `useEntity` enabled; disabling it lets the component use the submitted `id`, so your application must validate access to that record.

`commentsData` contains paginated comments restricted to the current table alias and record ID. Only flat display is implemented by the component. Its default query does not filter private or spam comments.

## Customize callbacks

Controller methods named `callbackCommentsInitType()`, `callbackCommentsView()`, `callbackCommentsFetchDataFlat()`, and `callbackCommentsPrepareParams()` override the corresponding component callbacks. For example, add visibility restrictions in your controller:

```php
public function callbackCommentsFetchDataFlat(array $options): \Cake\Datasource\Paging\PaginatedInterface {
    $comments = $this->fetchTable('Comments.Comments');
    $query = $comments->find()->where([
        $comments->aliasField('model') => 'Posts',
        $comments->aliasField('foreign_key') => $options['id'],
        $comments->aliasField('is_private') => false,
        $comments->aliasField('is_spam') => false,
    ]);

    return $this->paginate($query);
}
```

Adapt the table alias to your controller. A submitted `parent_id` must belong to the same model and record; the separate public controller always creates top-level comments.

## AJAX

AJAX requests set `ajaxMode` and a `redirect` view variable instead of returning a normal redirect. The component does not provide a JavaScript client or JSON response serializer. Your application must render and handle the response. Use normal form submission for the first setup.
