<?php

declare(strict_types=1);

namespace DbConfig\Model\Table;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\RulesChecker;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\ORM\Query\SelectQuery;
use DbConfig\Service\ConfigService;
use Cake\Log\Log;

/**
 * AppSettings Model
 *
 * @method \DbConfig\Model\Entity\AppSetting newEmptyEntity()
 * @method \DbConfig\Model\Entity\AppSetting newEntity(array $data, array $options = [])
 * @method array<\DbConfig\Model\Entity\AppSetting> newEntities(array $data, array $options = [])
 * @method \DbConfig\Model\Entity\AppSetting get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \DbConfig\Model\Entity\AppSetting findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \DbConfig\Model\Entity\AppSetting patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\DbConfig\Model\Entity\AppSetting> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \DbConfig\Model\Entity\AppSetting|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \DbConfig\Model\Entity\AppSetting saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\DbConfig\Model\Entity\AppSetting>|\Cake\Datasource\ResultSetInterface<\DbConfig\Model\Entity\AppSetting>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\DbConfig\Model\Entity\AppSetting>|\Cake\Datasource\ResultSetInterface<\DbConfig\Model\Entity\AppSetting> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\DbConfig\Model\Entity\AppSetting>|\Cake\Datasource\ResultSetInterface<\DbConfig\Model\Entity\AppSetting>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\DbConfig\Model\Entity\AppSetting>|\Cake\Datasource\ResultSetInterface<\DbConfig\Model\Entity\AppSetting> deleteManyOrFail(iterable $entities, array $options = [])
 */
class AppSettingsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('app_settings');
        $this->setDisplayField('key');
        $this->setPrimaryKey('id');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('config_key')
            ->maxLength('config_key', 255)
            ->requirePresence('config_key', 'create')
            ->notEmptyString('config_key');

        $validator
            ->scalar('value')
            ->requirePresence('value', 'create')
            ->notEmptyString('value');

        $validator
            ->scalar('type')
            ->maxLength('type', 255)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        return $validator;
    }

    public function afterSave(EventInterface $event, $entity, ArrayObject $options): void
    {
        ConfigService::reload();
    }

    public function afterDelete(EventInterface $event, $entity, ArrayObject $options): void
    {
        ConfigService::reload();
    }
}
