<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Comments\Model\Entity\Comment> $comments
 */
$cspNonce = (string)$this->getRequest()->getAttribute('cspNonce', '');
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
    <ul class="side-nav nav nav-pills flex-column">
        <li class="nav-item heading"><?= __d('comments', 'Actions') ?></li>
        <li class="nav-item">
        </li>
    </ul>
</nav>
<div class="comments index content large-9 medium-8 columns col-sm-8 col-12">

    <h2><?= __d('comments', 'Comments') ?></h2>

    <div class="">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('model') ?></th>
                    <th><?= $this->Paginator->sort('foreign_key') ?></th>
                    <th><?= $this->Paginator->sort('user_id') ?></th>
                    <th>
                        <?= $this->Paginator->sort('email') ?>
                    </th>
                    <th><?= $this->Paginator->sort('is_private') ?></th>
                    <th><?= $this->Paginator->sort('is_spam') ?></th>
                    <th><?= $this->Paginator->sort('created', null, ['direction' => 'desc']) ?></th>
                    <th><?= $this->Paginator->sort('modified', null, ['direction' => 'desc']) ?></th>
                    <th class="actions"><?= __d('comments', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): ?>
                <tr>
                    <td><?= h($comment->model) ?></td>
                    <td><?= h($comment->foreign_key) ?></td>
                    <td><?= $comment->hasValue('user') ? h($comment->user_id) : '' ?></td>
                    <td>
                        <?= h($comment->email) ?>
                        <?php if ($comment->name) { ?>
                            (<?= h($comment->name) ?>)
                        <?php } ?>
                    </td>
                    <td><?= $comment->is_private ? __d('comments', 'Yes') : __d('comments', 'No') ?></td>
                    <td><?= $comment->is_spam ? __d('comments', 'Yes') : __d('comments', 'No') ?></td>
                    <td><?= $this->Time->nice($comment->created) ?></td>
                    <td><?= $this->Time->nice($comment->modified) ?></td>
                    <td class="actions">
                        <?php echo $this->Html->link(__d('comments', 'View'), ['action' => 'view', $comment->id], ['escape' => false, 'escapeAttributes' => true]); ?>
                        <?php echo $this->Html->link(__d('comments', 'Edit'), ['action' => 'edit', $comment->id], ['escape' => false, 'escapeAttributes' => true]); ?>
                        <?php echo $this->Form->postButton(__d('comments', 'Delete'), ['action' => 'delete', $comment->id], [
                            'escape' => false, 'escapeAttributes' => true,
                            'class' => 'btn btn-link p-0 align-baseline',
                            'form' => [
                                'class' => 'd-inline',
                                'data-confirm-message' => __d('comments', 'Are you sure you want to delete # {0}?', $comment->id),
                            ],
                        ]); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <nav aria-label="<?= __d('comments', 'Pagination') ?>">
        <?= $this->Paginator->prev(__d('comments', 'Previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__d('comments', 'Next')) ?>
    </nav>
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
