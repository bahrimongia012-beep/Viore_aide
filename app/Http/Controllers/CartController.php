<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Commands;

/**
 * Patron Template Method — Classe concrète pour le panier client (serveur)
 *
 * Rôle : ConcreteClass
 * Hérite du squelette d'algorithme de AbstractPanierController
 * et implémente uniquement les étapes spécifiques au panier client.
 */
class CartController extends AbstractPanierController
{
    /**
     * Clé de session du panier client.
     */
    protected function getSessionKey(): string
    {
        return 'cart';
    }

    /**
     * Source de la commande : initiée depuis l'interface client/serveur.
     */
    protected function getSource(): string
    {
        return 'client';
    }

    /**
     * Validation spécifique au formulaire du panier client.
     * Les champs diffèrent de ceux du caissier (ex: 'date' au lieu de 'heure_arrivee').
     */
    protected function validerDonnees(Request $request): array
    {
        return $request->validate([
            'client'   => 'required|string|max:255',
            'date'     => 'required|date',
            'total'    => 'required|numeric',
            'produits' => 'required|array',
        ]);
    }

    /**
     * Construction de la commande avec les champs propres au panier client.
     * Le champ 'date' du formulaire est mappé vers 'heure_arrivee' du modèle.
     */
    protected function construireCommande(array $data, array $produits): Commands
    {
        $command = new Commands();
        $command->client        = $data['client'];
        $command->heure_arrivee = $data['date'];
        $command->total_price   = $data['total'];
        $command->type_commande = 'sur place';
        $command->branch        = 'branche1';

        return $command;
    }

    /**
     * Point d'entrée pour ajouter un produit au panier client.
     * Délégué à la méthode template de la classe abstraite.
     */
    public function addToCart(Request $request)
    {
        return $this->ajouterProduit($request);
    }
}
