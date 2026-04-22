<?php

namespace App\Services;

use App\Contracts\StatusStrategyInterface;
use App\Models\Categorie;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * GoF Pattern: Facade
 * 
 * Fournit une interface unifiée pour les opérations CRUD sur les entités du menu.
 * Simplifie l'accès aux sous-systèmes (modèles, upload photo, gestion de statut).
 */
class MenuService
{
    private StatusStrategyInterface $statusStrategy;

    public function __construct(StatusStrategyInterface $statusStrategy)
    {
        $this->statusStrategy = $statusStrategy;
    }

    /**
     * Crée une entité avec gestion automatique de la photo et de la catégorie.
     *
     * @param string $modelClass La classe du modèle à créer
     * @param array $data Les données validées
     * @param Request $request La requête HTTP (pour l'upload photo)
     * @param string|null $categoryFieldName Le nom du champ catégorie dans les données
     * @return Model L'entité créée
     */
    public function createEntity(
        string $modelClass,
        array $data,
        Request $request,
        ?string $categoryFieldName = null
    ): Model {
        // Gestion de la photo via PhotoFactory
        $data['photo'] = PhotoFactory::create($request);

        // Résolution de la catégorie parente si nécessaire
        if ($categoryFieldName && isset($data[$categoryFieldName])) {
            // Détermination de la classe parente
            $parentClass = \App\Models\Categorie::class; // Défaut
            if ($modelClass === \App\Models\Categorie::class) {
                $parentClass = \App\Models\Categoriep::class;
            } elseif ($modelClass === \App\Models\Optionmodif::class) {
                $parentClass = \App\Models\Modif::class;
            }

            $category = $parentClass::where('Nom', $data[$categoryFieldName])->firstOrFail();
            
            // Détermination de la clé étrangère
            $foreignKey = 'categorie_id'; // Défaut
            if ($modelClass === \App\Models\Categorie::class) {
                $foreignKey = 'categoriep_id';
            } elseif ($modelClass === \App\Models\Optionmodif::class) {
                $foreignKey = 'modificateur_id';
            }

            $data[$foreignKey] = $category->id;
            
            // IMPORTANT : On supprime le champ temporaire pour éviter l'erreur SQL
            unset($data[$categoryFieldName]);
        }

        return $modelClass::create($data);
    }

    /**
     * Bascule le statut d'une entité via la stratégie configurée.
     *
     * @param Model $entity
     * @return void
     */
    public function toggleStatus(Model $entity): void
    {
        $this->statusStrategy->toggle($entity);
    }

    /**
     * Suppression douce d'une entité.
     *
     * @param Model $entity
     * @return void
     */
    public function softDelete(Model $entity): void
    {
        $entity->delete();
    }

    /**
     * Restauration d'une entité supprimée.
     *
     * @param Model $entity
     * @return void
     */
    public function restore(Model $entity): void
    {
        $entity->restore();
    }

    /**
     * Met à jour une entité avec gestion optionnelle de la photo.
     *
     * @param Model $entity L'entité à mettre à jour
     * @param array $data Les données validées
     * @param Request $request La requête HTTP
     * @return Model L'entité mise à jour
     */
    public function updateEntity(Model $entity, array $data, Request $request, ?string $categoryFieldName = null): Model
    {
        // Mise à jour de la photo si un nouveau fichier est fourni
        $newPhoto = PhotoFactory::update($request);
        if ($newPhoto) {
            $data['photo'] = $newPhoto;
        }

        // Résolution de la catégorie si nécessaire
        if ($categoryFieldName && isset($data[$categoryFieldName])) {
            $parentClass = \App\Models\Categorie::class; // Par défaut
            if ($entity instanceof \App\Models\Categorie) {
                $parentClass = \App\Models\Categoriep::class;
            } elseif ($entity instanceof \App\Models\Optionmodif) {
                $parentClass = \App\Models\Modif::class;
            }
            
            $category = $parentClass::where('Nom', $data[$categoryFieldName])->firstOrFail();
            
            // On utilise la méthode de relation dynamique si possible
            $relationName = ($entity instanceof \App\Models\Optionmodif) ? 'modify' : (($entity instanceof \App\Models\Cartefidelite) ? 'category' : (($entity instanceof \App\Models\Categorie) ? 'categoriesp' : 'categorie'));
            if (method_exists($entity, $relationName)) {
                $entity->$relationName()->associate($category);
            }

            // IMPORTANT : On supprime le champ pour éviter l'erreur SQL
            unset($data[$categoryFieldName]);
        }

        $entity->update($data);
        return $entity;
    }
}
