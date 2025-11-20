<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Comments\Model\Entity\Comment> $comments
 */
?>
<nav class="actions large-3 medium-4 columns col-sm-4 col-xs-12" id="actions-sidebar">
    <ul class="side-nav nav nav-pills flex-column">
        <li class="nav-item heading"><?= __('Actions') ?></li>
        <li class="nav-item">
        </li>
    </ul>
</nav>
<div class="comments index content large-9 medium-8 columns col-sm-8 col-12">

    <h2><?= __('Comments') ?></h2>

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
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): ?>
                <tr>
                    <td><?= h($comment->model) ?></td>
                    <td><?= $this->Number->format($comment->foreign_key) ?></td>
                    <td><?= $comment->hasValue('user') ? $this->Html->link($comment->user->username, ['controller' => 'Users', 'action' => 'view', $comment->user->id]) : '' ?></td>
                    <td><?= h($comment->name) ?></td>
                    <td>
                        <?= h($comment->email) ?>
                        <?php if ($comment->name) { ?>
                            (<?= h($comment->name) ?>)
                        <?php } ?>
                    </td>
                    <td><?= $this->IconSnippet->yesNo($comment->is_private) ?></td>
                    <td><?= $this->IconSnippet->yesNo($comment->is_spam) ?></td>
                    <td><?= $this->Time->nice($comment->created) ?></td>
                    <td><?= $this->Time->nice($comment->modified) ?></td>
                    <td class="actions">
                        <?php echo $this->Html->link($this->Icon->render('view'), ['action' => 'view', $comment->id], ['escapeTitle' => false]); ?>
                        <?php echo $this->Html->link($this->Icon->render('edit'), ['action' => 'edit', $comment->id], ['escapeTitle' => false]); ?>
                        <?php echo $this->Form->postLink($this->Icon->render('delete'), ['action' => 'delete', $comment->id], ['escapeTitle' => false, 'confirm' => __('Are you sure you want to delete # {0}?', $comment->id)]); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php echo $this->element('Tools.pagination'); ?>
</div>
