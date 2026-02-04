<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\DbConfig\Model\Entity\AppSetting> $appSettings
 * @var int|string|null $id
 * @var bool $canUpdate
 */
?>
<div class="appSettings index content">
    <h3><?= __('App Settings') ?></h3>

    <?php if (!$canUpdate): ?>
    <div class="alert alert-info" style="padding: 10px; background: #e7f3ff; border: 1px solid #b6d4fe; border-radius: 4px; margin-bottom: 15px;">
        <?= __('You have read-only access to settings.') ?>
    </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('config_key') ?></th>
                    <th><?= $this->Paginator->sort('value') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appSettings as $appSetting): ?>
                    <tr>
                        <td><?= $this->Number->format($appSetting->id) ?></td>
                        <td><?= h($appSetting->config_key) ?></td>
                        <td>
                            <?php if ($canUpdate && $id == $appSetting->id): ?>
                                <?= $this->Form->create($appSetting) ?>
                                <?php if (strtolower($appSetting->type) === 'encrypted'): ?>
                                    <?= $this->Form->control('value', [
                                        'label' => false,
                                        'type' => 'password',
                                        'value' => '',
                                        'placeholder' => __('Enter new value or leave empty to keep existing'),
                                    ]) ?>
                                <?php else: ?>
                                    <?= $this->Form->control('value', ['label' => false]) ?>
                                <?php endif; ?>
                                <?= $this->Form->button(__('Submit')) ?>
                                <?= $this->Html->link(__('Cancel'), [
                                    'controller' => $this->request->getParam('controller'),
                                    'action' => $this->request->getParam('action'),
                                ], ['class' => 'button secondary']) ?>
                                <?= $this->Form->end() ?>
                            <?php else: ?>
                                <?php if (strtolower($appSetting->type) === 'encrypted'): ?>
                                    <span class="encrypted-value" title="<?= __('Encrypted value') ?>">********</span>
                                <?php else: ?>
                                    <?= h($appSetting->value) ?>
                                <?php endif; ?>
                                <?php if ($canUpdate): ?>
                                    <?= $this->Html->link(__('Edit'), ['?' => ['id' => $appSetting->id]], ['class' => 'float-right']) ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
