<?php
/**
 * @var \App\View\AppView $this
 * @var \Comments\Model\Entity\Comment $comment
 * @var string[]|\Cake\Collection\CollectionInterface $parentComments
 * @var string[]|\Cake\Collection\CollectionInterface $users
 */
$cspNonce = (string)$this->getRequest()->getAttribute('cspNonce', '');
?>
<div class="row">
    <aside class="column large-3 medium-4 columns col-sm-4 col-12">
        <ul class="side-nav nav nav-pills flex-column">
            <li class="nav-item heading"><?= __d('comments', 'Actions') ?></li>
            <li class="nav-item"><?= $this->Form->postButton(
                __d('comments', 'Delete'),
                ['action' => 'delete', $comment->id],
                [
                    'class' => 'side-nav-item btn btn-link text-start w-100',
                    'form' => [
                        'class' => 'd-inline',
                        'data-confirm-message' => __d('comments', 'Are you sure you want to delete # {0}?', $comment->id),
                    ],
                ]
                ) ?></li>
            <li class="nav-item"><?= $this->Html->link(__d('comments', 'List Comments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?></li>
        </ul>
    </aside>
    <div class="column-responsive column-80 form large-9 medium-8 columns col-sm-8 col-12">
        <div class="comments form content">
            <h2><?= __d('comments', 'Comments') ?></h2>

            <?= $this->Form->create($comment) ?>
            <fieldset>
                <legend><?= __d('comments', 'Edit Comment') ?></legend>
                <?php
                    echo $this->Form->control('model');
                    echo $this->Form->control('foreign_key');
                    echo $this->Form->control('name');
                    echo $this->Form->control('email');
                    echo $this->Form->control('content');
                    echo $this->Form->control('is_private');
                    echo $this->Form->control('is_spam');
                ?>
            </fieldset>
            <?= $this->Form->button(__d('comments', 'Submit')) ?>
            <?= $this->Form->end() ?>
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
