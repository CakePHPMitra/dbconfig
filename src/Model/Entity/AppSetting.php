<?php
declare(strict_types=1);

namespace DbConfig\Model\Entity;

use Cake\ORM\Entity;

/**
 * AppSetting Entity
 *
 * @property int $id
 * @property string $config_key
 * @property string $value
 * @property string $type
 */
class AppSetting extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'config_key' => true,
        'value' => true,
        'type' => true,
    ];
}
