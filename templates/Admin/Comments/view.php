<?php
/**
 * @var \App\View\AppView $this
 * @var \Comments\Model\Entity\Comment $comment
 */
?>
<div class="row">
    <aside class="column actions large-3 medium-4 col-sm-4 col-xs-12">
        <ul class="side-nav nav nav-pills flex-column">
            <li class="nav-item heading"><?= __('Actions') ?></li>
            <li class="nav-item"><?= $this->Html->link(__('Edit {0}', __('Comment')), ['action' => 'edit', $comment->id], ['class' => 'side-nav-item']) ?></li>
            <li class="nav-item"><?= $this->Form->postLink(__('Delete {0}', __('Comment')), ['action' => 'delete', $comment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $comment->id), 'class' => 'side-nav-item']) ?></li>
            <li class="nav-item"><?= $this->Html->link(__('List {0}', __('Comments')), ['action' => 'index'], ['class' => 'side-nav-item']) ?></li>
        </ul>
    </aside>
    <div class="column-responsive column-80 content large-9 medium-8 col-sm-8 col-xs-12">
        <div class="comments view content">
            <h2><?= h($comment->model) ?> #<?php echo h($comment->foreign_key); ?></h2>

            <table class="table table-striped">
                <tr>
                    <th><?= __('User') ?></th>
                    <td><?= $comment->hasValue('user') ? $this->Html->link($comment->user->username, ['controller' => 'Users', 'action' => 'view', $comment->user->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($comment->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Email') ?></th>
                    <td><?= h($comment->email) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= $this->Time->nice($comment->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= $this->Time->nice($comment->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Is Private') ?></th>
                    <td><?= $this->IconSnippet->yesNo($comment->is_private) ?> <?= $comment->is_private ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Is Spam') ?></th>
                    <td><?= $this->IconSnippet->yesNo($comment->is_spam) ?> <?= $comment->is_spam ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Content') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($comment->content)); ?>
                </blockquote>
            </div>
            <div class="related">
                <h4><?= __('Related Comments') ?></h4>
                <?php if (!empty($comment->child_comments)) : ?>
                <div>
                    <table class="table table-striped">
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Foreign Key') ?></th>
                            <th><?= __('Model') ?></th>
                            <th><?= __('User Id') ?></th>
                            <th><?= __('Name') ?></th>
                            <th><?= __('Email') ?></th>
                            <th><?= __('Content') ?></th>
                            <th><?= __('Is Private') ?></th>
                            <th><?= __('Is Spam') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
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
                                <?php echo $this->Form->postLink($this->Icon->render('delete'), ['controller' => 'Comments', 'action' => 'delete', $childComments->id], ['escapeTitle' => false, 'confirm' => __('Are you sure you want to delete # {0}?', $childComments->id)]); ?>
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
