<?php
/**
 * @var \App\View\AppView $this
 * @var \Comments\Model\Entity\Comment $comment
 * @var \Cake\Collection\CollectionInterface|string[] $parentComments
 * @var \Cake\Collection\CollectionInterface|string[] $users
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('comments', 'Actions') ?></h4>
            <?= $this->Html->link(__d('comments', 'List Comments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="comments form content">
            <?= $this->Form->create($comment) ?>
            <fieldset>
                <legend><?= __d('comments', 'Add Comment') ?></legend>
                <?php
                    echo $this->Form->control('foreign_key');
                    echo $this->Form->control('model');
                    echo $this->Form->control('user_id', ['options' => $users, 'empty' => true]);
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
