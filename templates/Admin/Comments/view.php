<?php
/**
 * @var \App\View\AppView $this
 * @var \Comments\Model\Entity\Comment $comment
 */
$cspNonce = (string)$this->getRequest()->getAttribute('cspNonce', '');
?>
<div class="row">
    <aside class="column actions large-3 medium-4 col-sm-4 col-xs-12">
        <ul class="side-nav nav nav-pills flex-column">
            <li class="nav-item heading"><?= __d('comments', 'Actions') ?></li>
            <li class="nav-item"><?= $this->Html->link(__d('comments', 'Edit {0}', __d('comments', 'Comment')), ['action' => 'edit', $comment->id], ['class' => 'side-nav-item']) ?></li>
            <li class="nav-item"><?= $this->Form->postButton(__d('comments', 'Delete {0}', __d('comments', 'Comment')), ['action' => 'delete', $comment->id], [
                'class' => 'side-nav-item btn btn-link text-start w-100',
                'form' => [
                    'class' => 'd-inline',
                    'data-confirm-message' => __d('comments', 'Are you sure you want to delete # {0}?', $comment->id),
                ],
            ]) ?></li>
            <li class="nav-item"><?= $this->Html->link(__d('comments', 'List {0}', __d('comments', 'Comments')), ['action' => 'index'], ['class' => 'side-nav-item']) ?></li>
        </ul>
    </aside>
    <div class="column-responsive column-80 content large-9 medium-8 col-sm-8 col-xs-12">
        <div class="comments view content">
            <h2><?= h($comment->model) ?> #<?php echo h($comment->foreign_key); ?></h2>

            <table class="table table-striped">
                <tr>
                    <th><?= __d('comments', 'User') ?></th>
                    <td><?= $comment->hasValue('user') ? $this->Html->link($comment->user->username, ['controller' => 'Users', 'action' => 'view', $comment->user->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __d('comments', 'Name') ?></th>
                    <td><?= h($comment->name) ?></td>
                </tr>
                <tr>
                    <th><?= __d('comments', 'Email') ?></th>
                    <td><?= h($comment->email) ?></td>
                </tr>
                <tr>
                    <th><?= __d('comments', 'Created') ?></th>
                    <td><?= $this->Time->nice($comment->created) ?></td>
                </tr>
                <tr>
                    <th><?= __d('comments', 'Modified') ?></th>
                    <td><?= $this->Time->nice($comment->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __d('comments', 'Is Private') ?></th>
                    <td><?= $this->IconSnippet->yesNo($comment->is_private) ?> <?= $comment->is_private ? __d('comments', 'Yes') : __d('comments', 'No'); ?></td>
                </tr>
                <tr>
                    <th><?= __d('comments', 'Is Spam') ?></th>
                    <td><?= $this->IconSnippet->yesNo($comment->is_spam) ?> <?= $comment->is_spam ? __d('comments', 'Yes') : __d('comments', 'No'); ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __d('comments', 'Content') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($comment->content)); ?>
                </blockquote>
            </div>
            <div class="related">
                <h4><?= __d('comments', 'Related Comments') ?></h4>
                <?php if (!empty($comment->child_comments)) : ?>
                <div>
                    <table class="table table-striped">
                        <tr>
                            <th><?= __d('comments', 'Id') ?></th>
                            <th><?= __d('comments', 'Foreign Key') ?></th>
                            <th><?= __d('comments', 'Model') ?></th>
                            <th><?= __d('comments', 'User Id') ?></th>
                            <th><?= __d('comments', 'Name') ?></th>
                            <th><?= __d('comments', 'Email') ?></th>
                            <th><?= __d('comments', 'Content') ?></th>
                            <th><?= __d('comments', 'Is Private') ?></th>
                            <th><?= __d('comments', 'Is Spam') ?></th>
                            <th><?= __d('comments', 'Created') ?></th>
                            <th><?= __d('comments', 'Modified') ?></th>
                            <th class="actions"><?= __d('comments', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($comment->child_comments as $childComments) : ?>
                        <tr>
                            <td><?= h($childComments->id) ?></td>
                            <td><?= h($childComments->foreign_key) ?></td>
                            <td><?= h($childComments->model) ?></td>
                            <td><?= h($childComments->user_id) ?></td>
                            <td><?= h($childComments->name) ?></td>
                            <td><?= h($childComments->email) ?></td>
                            <td><?= h($childComments->content) ?></td>
                            <td><?= h($childComments->is_private) ?></td>
                            <td><?= h($childComments->is_spam) ?></td>
                            <td><?= h($childComments->created) ?></td>
                            <td><?= h($childComments->modified) ?></td>
                            <td class="actions">
                                <?php echo $this->Html->link($this->Icon->render('view'), ['controller' => 'Comments', 'action' => 'view', $childComments->id], ['escapeTitle' => false]); ?>
                                <?php echo $this->Html->link($this->Icon->render('edit'), ['controller' => 'Comments', 'action' => 'edit', $childComments->id], ['escapeTitle' => false]); ?>
                                <?php echo $this->Form->postButton($this->Icon->render('delete'), ['controller' => 'Comments', 'action' => 'delete', $childComments->id], [
                                    'escapeTitle' => false,
                                    'class' => 'btn btn-link p-0 align-baseline',
                                    'form' => [
                                        'class' => 'd-inline',
                                        'data-confirm-message' => __d('comments', 'Are you sure you want to delete # {0}?', $childComments->id),
                                    ],
                                ]); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script<?= $cspNonce !== '' ? ' nonce="' . h($cspNonce) . '"' : '' ?>>
document.querySelectorAll('form[data-confirm-message]').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        if (!confirm(this.dataset.confirmMessage)) {
            e.preventDefault();
        }
    });
});
</script>
