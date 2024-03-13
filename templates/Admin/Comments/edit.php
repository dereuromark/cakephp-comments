<?php
/**
 * @var \App\View\AppView $this
 * @var \Comments\Model\Entity\Comment $comment
 * @var string[]|\Cake\Collection\CollectionInterface $parentComments
 * @var string[]|\Cake\Collection\CollectionInterface $users
 */
?>
<div class="row">
    <aside class="column large-3 medium-4 columns col-sm-4 col-12">
        <ul class="side-nav nav nav-pills flex-column">
            <li class="nav-item heading"><?= __('Actions') ?></li>
            <li class="nav-item"><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $comment->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $comment->id), 'class' => 'side-nav-item']
                ) ?></li>
            <li class="nav-item"><?= $this->Html->link(__('List Comments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?></li>
        </ul>
    </aside>
    <div class="column-responsive column-80 form large-9 medium-8 columns col-sm-8 col-12">
        <div class="comments form content">
            <h2><?= __('Comments') ?></h2>

            <?= $this->Form->create($comment) ?>
            <fieldset>
                <legend><?= __('Edit Comment') ?></legend>
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
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
