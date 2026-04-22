<?php

namespace App\Services\Strategies;

use App\Contracts\StatusStrategyInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * GoF Pattern: Strategy (implémentation concrète)
 * 
 * Stratégie de basculement entre les statuts 'Actif' et 'Inactif'.
 * Remplace les 5 blocs if/elseif identiques copiés-collés dans les contrôleurs.
 */
class ActiveInactiveStrategy implements StatusStrategyInterface
{
    private string $statusField;

    public function __construct(string $statusField = 'status')
    {
        $this->statusField = $statusField;
    }

    /**
     * Bascule le statut entre 'Actif' et 'Inactif'.
     *
     * @param Model $entity
     * @return void
     */
    public function toggle(Model $entity): void
    {
        // Détection automatique du champ (status ou statut)
        $field = isset($entity->status) ? 'status' : (isset($entity->statut) ? 'statut' : 'status');
        
        $entity->$field = ($entity->$field === 'Actif') ? 'Inactif' : 'Actif';
        $entity->save();
    }

    /**
     * @return string
     */
    public function getStatusField(): string
    {
        return $this->statusField;
    }
}
