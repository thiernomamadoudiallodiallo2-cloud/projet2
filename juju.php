<?php
session_start();

// Configuration
define('DATA_FILE', 'comptable_data.json');
define('BACKUP_DIR', 'backups/');
date_default_timezone_set('Europe/Paris');

// Fonctions utilitaires
function chargerDonnees($fichier) {
    if (file_exists($fichier)) {
        return json_decode(file_get_contents($fichier), true) ?: [];
    }
    return [];
}

function sauvegarderDonnees($fichier, $donnees) {
    file_put_contents($fichier, json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function formatMontant($montant) {
    return number_format($montant, 2, ',', ' ') . ' €';
}

function dateFrancaise($date) {
    return date('d/m/Y', strtotime($date));
}

// Initialisation des dossiers
if (!file_exists(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

// Initialisation des données
if (!file_exists(DATA_FILE)) {
    $donneesInitiales = [
        'categories' => [
            ['id' => 1, 'nom' => 'Salaire', 'type' => 'recette', 'couleur' => '#4CAF50'],
            ['id' => 2, 'nom' => 'Loyer', 'type' => 'depense', 'couleur' => '#F44336'],
            ['id' => 3, 'nom' => 'Alimentation', 'type' => 'depense', 'couleur' => '#FF9800'],
            ['id' => 4, 'nom' => 'Transport', 'type' => 'depense', 'couleur' => '#2196F3'],
            ['id' => 5, 'nom' => 'Santé', 'type' => 'depense', 'couleur' => '#9C27B0'],
            ['id' => 6, 'nom' => 'Loisirs', 'type' => 'depense', 'couleur' => '#00BCD4'],
            ['id' => 7, 'nom' => 'Divers', 'type' => 'depense', 'couleur' => '#795548'],
            ['id' => 8, 'nom' => 'Ventes', 'type' => 'recette', 'couleur' => '#8BC34A'],
            ['id' => 9, 'nom' => 'Amortissement', 'type' => 'depense', 'couleur' => '#607D8B'],
            ['id' => 10, 'nom' => 'Immobilisations', 'type' => 'depense', 'couleur' => '#3F51B5'],
            ['id' => 11, 'nom' => 'Stocks', 'type' => 'depense', 'couleur' => '#FF5722']
        ],
        'transactions' => [],
        'budgets' => [],
        'objectifs' => [],
        'factures_recurrentes' => [],
        'remboursements' => [],
        'immobilisations' => [],
        'journaux' => [
            'achats' => [],
            'ventes' => [],
            'banque' => [],
            'caisse' => []
        ],
        'amortissements' => [],
        'comptes' => [
            ['numero' => '512', 'nom' => 'Banque', 'type' => 'actif'],
            ['numero' => '53', 'nom' => 'Caisse', 'type' => 'actif'],
            ['numero' => '401', 'nom' => 'Fournisseurs', 'type' => 'passif'],
            ['numero' => '411', 'nom' => 'Clients', 'type' => 'actif'],
            ['numero' => '607', 'nom' => 'Achats de marchandises', 'type' => 'charge'],
            ['numero' => '701', 'nom' => 'Ventes de marchandises', 'type' => 'produit'],
            ['numero' => '681', 'nom' => 'Dotations aux amortissements', 'type' => 'charge'],
            ['numero' => '28', 'nom' => 'Amortissements', 'type' => 'actif']
        ],
        'chat_history' => []
    ];
    sauvegarderDonnees(DATA_FILE, $donneesInitiales);
}

// Assistant IA - Système de réponse intelligent
class AssistantComptableIA {
    private $donnees;
    private $categories;
    private $transactions;
    private $immobilisations;
    private $budgets;
    private $objectifs;
    
    public function __construct($donnees) {
        $this->donnees = $donnees;
        $this->categories = $donnees['categories'] ?? [];
        $this->transactions = $donnees['transactions'] ?? [];
        $this->immobilisations = $donnees['immobilisations'] ?? [];
        $this->budgets = $donnees['budgets'] ?? [];
        $this->objectifs = $donnees['objectifs'] ?? [];
    }
    
    public function analyserQuestion($question) {
        $question_lower = strtolower($question);
        
        // Calculer des statistiques pour les réponses
        $stats = $this->calculerStatistiques();
        
        // Détecter l'intention de la question
        if (strpos($question_lower, 'bonjour') !== false || strpos($question_lower, 'salut') !== false || strpos($question_lower, 'coucou') !== false) {
            return $this->reponseSalutation();
        }
        
        if (strpos($question_lower, 'solde') !== false || strpos($question_lower, 'combien') !== false) {
            return $this->reponseSolde($stats);
        }
        
        if (strpos($question_lower, 'dépense') !== false || strpos($question_lower, 'dépenses') !== false) {
            return $this->reponseDepenses($stats);
        }
        
        if (strpos($question_lower, 'recette') !== false || strpos($question_lower, 'recettes') !== false) {
            return $this->reponseRecettes($stats);
        }
        
        if (strpos($question_lower, 'budget') !== false) {
            return $this->reponseBudgets();
        }
        
        if (strpos($question_lower, 'amortissement') !== false || strpos($question_lower, 'immobilisation') !== false) {
            return $this->reponseAmortissements();
        }
        
        if (strpos($question_lower, 'catégorie') !== false || strpos($question_lower, 'categorie') !== false) {
            return $this->reponseCategories();
        }
        
        if (strpos($question_lower, 'transaction') !== false) {
            return $this->reponseTransactions();
        }
        
        if (strpos($question_lower, 'aide') !== false || strpos($question_lower, 'que peux-tu') !== false) {
            return $this->reponseAide();
        }
        
        if (strpos($question_lower, 'comment') !== false) {
            return $this->reponseComment($question_lower);
        }
        
        if (strpos($question_lower, 'quand') !== false) {
            return $this->reponseQuand($question_lower);
        }
        
        if (strpos($question_lower, 'pourquoi') !== false) {
            return $this->reponsePourquoi($question_lower);
        }
        
        // Réponse par défaut avec analyse contextuelle
        return $this->reponseGenerique($question, $stats);
    }
    
    private function calculerStatistiques() {
        $total_recettes = 0;
        $total_depenses = 0;
        $depenses_par_categorie = [];
        $recettes_par_categorie = [];
        
        foreach($this->transactions as $t) {
            if ($t['type'] === 'recette') {
                $total_recettes += $t['montant'];
                $recettes_par_categorie[$t['categorie_id']] = ($recettes_par_categorie[$t['categorie_id']] ?? 0) + $t['montant'];
            } else {
                $total_depenses += $t['montant'];
                $depenses_par_categorie[$t['categorie_id']] = ($depenses_par_categorie[$t['categorie_id']] ?? 0) + $t['montant'];
            }
        }
        
        // Trouver la catégorie avec le plus de dépenses
        $categorie_max_depense = null;
        $max_depense = 0;
        foreach($depenses_par_categorie as $cat_id => $montant) {
            if ($montant > $max_depense) {
                $max_depense = $montant;
                $categorie_max_depense = $cat_id;
            }
        }
        
        // Trouver la catégorie avec le plus de recettes
        $categorie_max_recette = null;
        $max_recette = 0;
        foreach($recettes_par_categorie as $cat_id => $montant) {
            if ($montant > $max_recette) {
                $max_recette = $montant;
                $categorie_max_recette = $cat_id;
            }
        }
        
        $solde = $total_recettes - $total_depenses;
        
        return [
            'total_recettes' => $total_recettes,
            'total_depenses' => $total_depenses,
            'solde' => $solde,
            'depenses_par_categorie' => $depenses_par_categorie,
            'recettes_par_categorie' => $recettes_par_categorie,
            'categorie_max_depense' => $categorie_max_depense,
            'categorie_max_recette' => $categorie_max_recette,
            'max_depense' => $max_depense,
            'max_recette' => $max_recette,
            'nb_transactions' => count($this->transactions),
            'nb_immobilisations' => count($this->immobilisations),
            'nb_budgets' => count($this->budgets),
            'nb_objectifs' => count($this->objectifs)
        ];
    }
    
    private function reponseSalutation() {
        $salutations = [
            "👋 Bonjour ! Je suis votre assistant comptable intelligent. Comment puis-je vous aider aujourd'hui ?",
            "🤖 Salut ! Je suis là pour vous aider avec vos questions comptables. Que souhaitez-vous savoir ?",
            "💼 Bonjour ! Assistant comptable IA à votre service. Posez-moi vos questions sur vos finances.",
            "📊 Hello ! Prêt à analyser vos données financières. Quelle est votre question ?"
        ];
        
        return $salutations[array_rand($salutations)];
    }
    
    private function reponseSolde($stats) {
        $solde = $stats['solde'];
        $total_recettes = $stats['total_recettes'];
        $total_depenses = $stats['total_depenses'];
        
        if ($solde > 0) {
            $messages = [
                "💰 Excellent ! Votre solde est positif de **" . formatMontant($solde) . "**.\n\n" .
                "📈 Recettes totales : " . formatMontant($total_recettes) . "\n" .
                "📉 Dépenses totales : " . formatMontant($total_depenses) . "\n\n" .
                "✅ Vous êtes en bonne santé financière !",
                
                "🎉 Félicitations ! Votre compte présente un excédent de **" . formatMontant($solde) . "**.\n\n" .
                "Vos recettes dépassent vos dépenses, c'est une excellente nouvelle !"
            ];
        } elseif ($solde < 0) {
            $messages = [
                "⚠️ Attention ! Votre solde est négatif de **" . formatMontant(abs($solde)) . "**.\n\n" .
                "📈 Recettes totales : " . formatMontant($total_recettes) . "\n" .
                "📉 Dépenses totales : " . formatMontant($total_depenses) . "\n\n" .
                "💡 Conseil : Essayez de réduire vos dépenses ou d'augmenter vos recettes.",
                
                "🔴 Déséquilibre détecté : vos dépenses dépassent vos recettes de **" . formatMontant(abs($solde)) . "**.\n" .
                "Il serait prudent de revoir votre budget."
            ];
        } else {
            $messages = [
                "⚖️ Votre solde est équilibré à **0 €**.\n\n" .
                "Vos recettes et dépenses sont parfaitement égales.",
                
                "📐 Équilibre parfait ! Recettes = Dépenses = " . formatMontant($total_recettes)
            ];
        }
        
        return $messages[array_rand($messages)];
    }
    
    private function reponseDepenses($stats) {
        $total_depenses = $stats['total_depenses'];
        $max_depense = $stats['max_depense'];
        $categorie_max = $stats['categorie_max_depense'];
        
        if ($total_depenses == 0) {
            return "📭 Aucune dépense enregistrée pour le moment. C'est un bon début !";
        }
        
        $categorie_nom = 'Inconnue';
        if ($categorie_max) {
            foreach($this->categories as $c) {
                if ($c['id'] == $categorie_max) {
                    $categorie_nom = $c['nom'];
                    break;
                }
            }
        }
        
        $messages = [
            "📉 Vous avez dépensé un total de **" . formatMontant($total_depenses) . "**.\n\n" .
            "🏆 Catégorie la plus importante : **" . $categorie_nom . "** (" . formatMontant($max_depense) . ")\n\n" .
            "💡 Conseil : Surveillez cette catégorie pour optimiser vos dépenses.",
            
            "💸 Total des dépenses : **" . formatMontant($total_depenses) . "**\n" .
            "La catégorie **" . $categorie_nom . "** représente votre poste de dépense principal."
        ];
        
        return $messages[array_rand($messages)];
    }
    
    private function reponseRecettes($stats) {
        $total_recettes = $stats['total_recettes'];
        $max_recette = $stats['max_recette'];
        $categorie_max = $stats['categorie_max_recette'];
        
        if ($total_recettes == 0) {
            return "📭 Aucune recette enregistrée pour le moment. Pensez à ajouter vos sources de revenus !";
        }
        
        $categorie_nom = 'Inconnue';
        if ($categorie_max) {
            foreach($this->categories as $c) {
                if ($c['id'] == $categorie_max) {
                    $categorie_nom = $c['nom'];
                    break;
                }
            }
        }
        
        $messages = [
            "📈 Vous avez encaissé un total de **" . formatMontant($total_recettes) . "**.\n\n" .
            "🏆 Source principale de revenus : **" . $categorie_nom . "** (" . formatMontant($max_recette) . ")\n\n" .
            "💡 Conseil : Développez cette source de revenus pour améliorer votre situation.",
            
            "💵 Total des recettes : **" . formatMontant($total_recettes) . "**\n" .
            "Votre principale source de revenus est **" . $categorie_nom . "**."
        ];
        
        return $messages[array_rand($messages)];
    }
    
    private function reponseBudgets() {
        if (empty($this->budgets)) {
            return "📋 Aucun budget défini pour le moment. \n💡 Conseil : Définissez des budgets pour mieux contrôler vos dépenses.";
        }
        
        $message = "📅 **Budgets définis** :\n\n";
        
        foreach($this->budgets as $budget) {
            $categorie_nom = 'Inconnue';
            foreach($this->categories as $c) {
                if ($c['id'] == $budget['categorie_id']) {
                    $categorie_nom = $c['nom'];
                    break;
                }
            }
            
            $message .= "• **" . $categorie_nom . "** : " . formatMontant($budget['montant']) . " (" . $budget['periode'] . ")\n";
        }
        
        $message .= "\n💡 Conseil : Réviser régulièrement vos budgets permet une meilleure gestion.";
        
        return $message;
    }
    
    private function reponseAmortissements() {
        if (empty($this->immobilisations)) {
            return "🏢 Aucune immobilisation enregistrée. \n💡 Conseil : Ajoutez vos immobilisations pour calculer les amortissements.";
        }
        
        $valeur_totale = 0;
        foreach($this->immobilisations as $immobilisation) {
            $valeur_totale += $immobilisation['valeur_acquisition'];
        }
        
        $message = "📊 **Immobilisations enregistrées** :\n\n";
        $message .= "• Nombre d'immobilisations : " . count($this->immobilisations) . "\n";
        $message .= "• Valeur totale : **" . formatMontant($valeur_totale) . "**\n\n";
        
        foreach($this->immobilisations as $immobilisation) {
            $message .= "• **" . $immobilisation['nom'] . "** : " . formatMontant($immobilisation['valeur_acquisition']) . 
                       " (" . $immobilisation['methode_amortissement'] . " sur " . $immobilisation['duree_amortissement'] . " ans)\n";
        }
        
        $message .= "\n💡 Conseil : Les amortissements permettent de répartir le coût des immobilisations sur leur durée d'utilisation.";
        
        return $message;
    }
    
    private function reponseCategories() {
        $depenses_categories = [];
        $recettes_categories = [];
        
        foreach($this->categories as $categorie) {
            if ($categorie['type'] === 'depense') {
                $depenses_categories[] = $categorie['nom'];
            } else {
                $recettes_categories[] = $categorie['nom'];
            }
        }
        
        $message = "🏷️ **Catégories disponibles** :\n\n";
        $message .= "📉 **Dépenses** :\n";
        $message .= implode(", ", $depenses_categories) . "\n\n";
        $message .= "📈 **Recettes** :\n";
        $message .= implode(", ", $recettes_categories);
        
        return $message;
    }
    
    private function reponseTransactions() {
        $nb_transactions = count($this->transactions);
        
        if ($nb_transactions == 0) {
            return "📭 Aucune transaction enregistrée. \n💡 Conseil : Commencez par ajouter vos premières transactions !";
        }
        
        // Dernière transaction
        $derniere_transaction = end($this->transactions);
        $categorie_nom = 'Inconnue';
        foreach($this->categories as $c) {
            if ($c['id'] == $derniere_transaction['categorie_id']) {
                $categorie_nom = $c['nom'];
                break;
            }
        }
        
        $message = "💼 **Transactions** :\n\n";
        $message .= "• Nombre total : **" . $nb_transactions . "** transactions\n";
        $message .= "• Dernière transaction : **" . $derniere_transaction['description'] . "**\n";
        $message .= "  Montant : " . formatMontant($derniere_transaction['montant']) . "\n";
        $message .= "  Catégorie : " . $categorie_nom . "\n";
        $message .= "  Date : " . dateFrancaise($derniere_transaction['date']) . "\n\n";
        
        $message .= "💡 Conseil : Consultez l'onglet 'Transactions' pour voir toutes vos opérations.";
        
        return $message;
    }
    
    private function reponseAide() {
        $message = "🤖 **Assistant Comptable IA - Guide d'utilisation**\n\n";
        $message .= "Je peux vous aider avec :\n\n";
        $message .= "💰 **Solde et finances** :\n";
        $message .= "• \"Quel est mon solde ?\"\n";
        $message .= "• \"Combien ai-je dépensé ?\"\n";
        $message .= "• \"Quelles sont mes recettes ?\"\n\n";
        
        $message .= "📊 **Analyse** :\n";
        $message .= "• \"Quelle est ma catégorie de dépense principale ?\"\n";
        $message .= "• \"Comment vont mes budgets ?\"\n";
        $message .= "• \"Montre-moi mes immobilisations\"\n\n";
        
        $message .= "💡 **Conseils** :\n";
        $message .= "• \"Comment réduire mes dépenses ?\"\n";
        $message .= "• \"Comment améliorer mon épargne ?\"\n";
        $message .= "• \"Qu'est-ce que l'amortissement dégressif ?\"\n\n";
        
        $message .= "📚 **Informations** :\n";
        $message .= "• \"Explique-moi les amortissements\"\n";
        $message .= "• \"Qu'est-ce qu'un bilan comptable ?\"\n";
        $message .= "• \"Comment fonctionne la TVA ?\"\n\n";
        
        $message .= "💬 **Posez-moi n'importe quelle question comptable !**";
        
        return $message;
    }
    
    private function reponseComment($question) {
        $reponses = [
            'comment ajouter' => "Pour ajouter une transaction :\n1. Allez dans l'onglet 'Transactions'\n2. Cliquez sur '➕ Nouvelle transaction'\n3. Remplissez le formulaire\n4. Cliquez sur 'Enregistrer'",
            
            'comment voir' => "Pour consulter vos données :\n1. Utilisez les onglets de navigation\n2. 'Tableau de bord' pour un aperçu\n3. 'Transactions' pour la liste complète\n4. 'Graphiques' pour les visualisations",
            
            'comment exporter' => "Pour exporter vos données :\n1. Allez dans l'onglet 'Transactions'\n2. Cliquez sur '📥 Exporter en CSV'\n3. Le fichier se téléchargera automatiquement",
            
            'comment definir' => "Pour définir un budget :\n1. Allez dans l'onglet 'Budgets'\n2. Remplissez le formulaire\n3. Choisissez la catégorie et le montant\n4. Cliquez sur 'Définir le budget'",
            
            'comment calculer' => "Pour calculer un amortissement :\n1. Allez dans l'onglet 'Immobilisations'\n2. Ajoutez une nouvelle immobilisation\n3. Choisissez la méthode (linéaire, dégressif, accéléré)\n4. Le plan d'amortissement sera généré automatiquement"
        ];
        
        foreach($reponses as $mot_cle => $reponse) {
            if (strpos($question, $mot_cle) !== false) {
                return $reponse;
            }
        }
        
        return "Pour répondre à votre question 'comment', voici quelques conseils généraux :\n\n" .
               "1. Naviguez dans les différents onglets\n" .
               "2. Utilisez les formulaires pour ajouter des données\n" .
               "3. Consultez les rapports pour l'analyse\n" .
               "4. N'hésitez pas à poser des questions plus précises !";
    }
    
    private function reponseQuand($question) {
        if (strpos($question, 'quand payer') !== false || strpos($question, 'quand déclarer') !== false) {
            return "📅 **Calendrier des échéances** :\n\n" .
                   "• TVA : Déclaration mensuelle ou trimestrielle (avant le 20 du mois suivant)\n" .
                   "• Impôt sur les sociétés : 4 acomptes (15/03, 15/06, 15/09, 15/12) + solde\n" .
                   "• Charges sociales : Mensuelles (avant le 5 du mois suivant)\n" .
                   "• CVAE : 2 acomptes (15/06 et 15/09) + solde\n\n" .
                   "💡 Conseil : Configurez des rappels dans votre calendrier !";
        }
        
        return "Pour les échéances importantes :\n\n" .
               "• Déclarations fiscales : Dates variables selon votre régime\n" .
               "• Paiements : Respectez les délais légaux\n" .
               "• Rapports : Généralement annuels\n\n" .
               "📆 Consultez un expert-comptable pour un calendrier personnalisé.";
    }
    
    private function reponsePourquoi($question) {
        $reponses = [
            'pourquoi amortir' => "📉 **Pourquoi amortir les immobilisations ?**\n\n" .
                                 "1. **Principe comptable** : Répartir le coût sur la durée d'utilisation\n" .
                                 "2. **Fiscalité** : Réduire le résultat imposable\n" .
                                 "3. **Économie** : Refléter la dépréciation des actifs\n" .
                                 "4. **Analyse** : Connaître la valeur réelle du patrimoine",
            
            'pourquoi budget' => "📋 **Pourquoi établir un budget ?**\n\n" .
                                "1. **Contrôle** : Maîtriser vos dépenses\n" .
                                "2. **Prévision** : Anticiper les besoins financiers\n" .
                                "3. **Décision** : Prendre de meilleures décisions d'investissement\n" .
                                "4. **Performance** : Améliorer la rentabilité",
            
            'pourquoi compta' => "📊 **Pourquoi tenir une comptabilité ?**\n\n" .
                                "1. **Légal** : Obligation légale pour les entreprises\n" .
                                "2. **Gestion** : Connaître la santé financière\n" .
                                "3. **Fiscal** : Calculer les impôts dus\n" .
                                "4. **Bancaire** : Faciliter l'obtention de crédits"
        ];
        
        foreach($reponses as $mot_cle => $reponse) {
            if (strpos($question, $mot_cle) !== false) {
                return $reponse;
            }
        }
        
        return "💡 **Réponse générale aux 'pourquoi'** :\n\n" .
               "La comptabilité et la gestion financière sont essentielles pour :\n" .
               "• Prendre des décisions éclairées\n" .
               "• Respecter les obligations légales\n" .
               "• Optimiser la performance financière\n" .
               "• Préparer l'avenir de votre entreprise";
    }
    
    private function reponseGenerique($question, $stats) {
        // Analyse des mots-clés pour une réponse contextuelle
        $mots_cles = [
            'tva' => "💰 **TVA (Taxe sur la Valeur Ajoutée)** :\n\n" .
                    "La TVA est un impôt indirect sur la consommation.\n" .
                    "• Taux normal : 20%\n" .
                    "• Taux réduit : 10%, 5.5%\n" .
                    "• Taux intermédiaire : 20%\n\n" .
                    "Déclaration : Mensuelle ou trimestrielle selon le régime.",
            
            'bilan' => "📄 **Bilan comptable** :\n\n" .
                      "Document présentant la situation patrimoniale à une date donnée.\n\n" .
                      "**ACTIF** = Ce que l'entreprise possède\n" .
                      "**PASSIF** = Ce que l'entreprise doit\n\n" .
                      "Équation fondamentale : ACTIF = PASSIF",
            
            'resultat' => "📈 **Compte de résultat** :\n\n" .
                         "Document retraçant les produits et charges sur une période.\n\n" .
                         "Formule : Résultat = Produits - Charges\n\n" .
                         "• Résultat > 0 : Bénéfice\n" .
                         "• Résultat < 0 : Perte",
            
            'cash flow' => "💸 **Cash flow (flux de trésorerie)** :\n\n" .
                          "Mouvement réel d'argent entrant et sortant.\n\n" .
                          "Différent du résultat comptable (qui inclut les provisions).\n" .
                          "Essentiel pour la survie de l'entreprise.",
            
            'credit' => "🏦 **Crédit et financement** :\n\n" .
                       "Plusieurs types de crédits disponibles :\n" .
                       "• Crédit court terme : Besoin en fonds de roulement\n" .
                       "• Crédit moyen terme : Investissements matériels\n" .
                       "• Crédit long terme : Immobilisations importantes",
            
            'impot' => "🏛️ **Impôts des entreprises** :\n\n" .
                      "Principaux impôts :\n" .
                      "• Impôt sur les sociétés (IS) : 15-25% du bénéfice\n" .
                      "• TVA : Taxe sur la consommation\n" .
                      "• CVAE : Contribution sur la valeur ajoutée\n" .
                      "• Taxe foncière : Pour les locaux"
        ];
        
        foreach($mots_cles as $mot_cle => $reponse) {
            if (strpos(strtolower($question), $mot_cle) !== false) {
                return $reponse;
            }
        }
        
        // Réponses aléatoires intelligentes
        $reponses_generiques = [
            "🤔 Je ne suis pas sûr de comprendre votre question. Pourriez-vous la reformuler ?\n\n" .
            "💡 Voici ce que je peux vous dire :\n" .
            "• Votre solde actuel est de " . formatMontant($stats['solde']) . "\n" .
            "• Vous avez " . $stats['nb_transactions'] . " transactions enregistrées\n" .
            "• " . count($this->immobilisations) . " immobilisations suivies",
            
            "🧠 Voici une analyse de votre situation :\n\n" .
            "📊 **Statistiques** :\n" .
            "• Transactions : " . $stats['nb_transactions'] . "\n" .
            "• Immobilisations : " . $stats['nb_immobilisations'] . "\n" .
            "• Budgets : " . $stats['nb_budgets'] . "\n" .
            "• Objectifs : " . $stats['nb_objectifs'] . "\n\n" .
            "💡 Conseil : Posez-moi des questions précises pour des réponses plus utiles !",
            
            "💼 En tant qu'assistant comptable, je peux vous aider avec :\n" .
            "• L'analyse de vos finances\n" .
            "• Les conseils de gestion\n" .
            "• Les explications comptables\n" .
            "• Les calculs financiers\n\n" .
            "Essayez des questions comme :\n" .
            "\"Quel est mon solde ?\"\n" .
            "\"Comment réduire mes dépenses ?\"\n" .
            "\"Explique-moi les amortissements\""
        ];
        
        return $reponses_generiques[array_rand($reponses_generiques)];
    }
    
    public function genererConseilIntelligent() {
        $stats = $this->calculerStatistiques();
        
        if ($stats['nb_transactions'] == 0) {
            return "💡 **Conseil du jour** : Commencez par enregistrer vos premières transactions ! C'est le premier pas vers une bonne gestion financière.";
        }
        
        if ($stats['solde'] < 0) {
            return "⚠️ **Alerte** : Votre solde est négatif. Pensez à :\n1. Réviser vos dépenses\n2. Chercher de nouvelles sources de revenus\n3. Établir un budget strict";
        }
        
        if ($stats['nb_budgets'] == 0) {
            return "📋 **Conseil** : Définissez des budgets ! C'est essentiel pour maîtriser vos finances et atteindre vos objectifs.";
        }
        
        if ($stats['nb_immobilisations'] > 0 && empty($this->donnees['amortissements'])) {
            return "📉 **Rappel** : N'oubliez pas de calculer les amortissements pour vos immobilisations. Cela impacte votre résultat fiscal.";
        }
        
        $conseils = [
            "💡 **Conseil d'optimisation** : Revoyez régulièrement vos catégories de dépenses. Identifier les postes où vous pouvez réduire est clé.",
            "📈 **Stratégie** : Diversifiez vos sources de revenus pour plus de stabilité financière.",
            "💰 **Épargne** : Essayez de mettre de côté au moins 10% de vos recettes chaque mois.",
            "📊 **Analyse** : Consultez vos rapports mensuels pour suivre l'évolution de votre situation financière.",
            "🎯 **Objectif** : Fixez-vous un objectif financi concret. C'est plus motivant et efficace !"
        ];
        
        return $conseils[array_rand($conseils)];
    }
    
    public function analyserTendances() {
        if (count($this->transactions) < 10) {
            return "📊 **Analyse** : Pas assez de données pour analyser les tendances. Continuez à enregistrer vos transactions !";
        }
        
        // Regrouper par mois
        $transactions_par_mois = [];
        foreach($this->transactions as $t) {
            $mois = substr($t['date'], 0, 7);
            if (!isset($transactions_par_mois[$mois])) {
                $transactions_par_mois[$mois] = ['recettes' => 0, 'depenses' => 0];
            }
            
            if ($t['type'] === 'recette') {
                $transactions_par_mois[$mois]['recettes'] += $t['montant'];
            } else {
                $transactions_par_mois[$mois]['depenses'] += $t['montant'];
            }
        }
        
        // Trier par mois
        ksort($transactions_par_mois);
        
        if (count($transactions_par_mois) < 2) {
            return "📈 **Tendance** : Pas assez de données mensuelles pour analyser les tendances.";
        }
        
        $mois_keys = array_keys($transactions_par_mois);
        $dernier_mois = end($mois_keys);
        $avant_dernier_mois = prev($mois_keys);
        
        $evolution_recettes = $transactions_par_mois[$dernier_mois]['recettes'] - $transactions_par_mois[$avant_dernier_mois]['recettes'];
        $evolution_depenses = $transactions_par_mois[$dernier_mois]['depenses'] - $transactions_par_mois[$avant_dernier_mois]['depenses'];
        
        $message = "📈 **Analyse des tendances** :\n\n";
        $message .= "Dernier mois ($dernier_mois) :\n";
        $message .= "• Recettes : " . formatMontant($transactions_par_mois[$dernier_mois]['recettes']) . "\n";
        $message .= "• Dépenses : " . formatMontant($transactions_par_mois[$dernier_mois]['depenses']) . "\n\n";
        
        if ($evolution_recettes > 0) {
            $message .= "✅ Vos recettes ont augmenté de " . formatMontant($evolution_recettes) . "\n";
        } elseif ($evolution_recettes < 0) {
            $message .= "⚠️ Vos recettes ont baissé de " . formatMontant(abs($evolution_recettes)) . "\n";
        } else {
            $message .= "➡️ Vos recettes sont stables\n";
        }
        
        if ($evolution_depenses > 0) {
            $message .= "⚠️ Vos dépenses ont augmenté de " . formatMontant($evolution_depenses) . "\n";
        } elseif ($evolution_depenses < 0) {
            $message .= "✅ Vos dépenses ont baissé de " . formatMontant(abs($evolution_depenses)) . "\n";
        } else {
            $message .= "➡️ Vos dépenses sont stables\n";
        }
        
        return $message;
    }
}

// Traitement des actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$message = '';
$typeMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch($action) {
        case 'connexion':
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            
            $users = [
                'admin' => ['password' => 'admin123', 'nom' => 'Administrateur', 'role' => 'admin', 'email' => 'admin@comptable.fr'],
                'comptable' => ['password' => 'compta123', 'nom' => 'Comptable', 'role' => 'comptable', 'email' => 'compta@entreprise.fr'],
                'user' => ['password' => 'user123', 'nom' => 'Utilisateur', 'role' => 'user', 'email' => 'user@entreprise.fr']
            ];
            
            if (isset($users[$username]) && $users[$username]['password'] === $password) {
                $_SESSION['user'] = [
                    'username' => $username,
                    'nom' => $users[$username]['nom'],
                    'role' => $users[$username]['role'],
                    'email' => $users[$username]['email']
                ];
                $message = 'Connexion réussie !';
                $typeMessage = 'success';
            } else {
                $message = 'Identifiants incorrects';
                $typeMessage = 'error';
            }
            break;
            
        case 'poser_question':
            if (isset($_SESSION['user'])) {
                $question = trim($_POST['question'] ?? '');
                
                if (!empty($question)) {
                    $donnees = chargerDonnees(DATA_FILE);
                    
                    // Initialiser l'assistant IA
                    $assistant = new AssistantComptableIA($donnees);
                    
                    // Obtenir la réponse
                    $reponse = $assistant->analyserQuestion($question);
                    
                    // Ajouter à l'historique du chat
                    $chat_entry = [
                        'id' => uniqid(),
                        'question' => $question,
                        'reponse' => $reponse,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'user' => $_SESSION['user']['username']
                    ];
                    
                    $donnees['chat_history'][] = $chat_entry;
                    
                    // Limiter l'historique à 50 messages
                    if (count($donnees['chat_history']) > 50) {
                        $donnees['chat_history'] = array_slice($donnees['chat_history'], -50);
                    }
                    
                    sauvegarderDonnees(DATA_FILE, $donnees);
                    
                    // Retourner la réponse
                    echo json_encode([
                        'success' => true,
                        'reponse' => $reponse,
                        'timestamp' => date('H:i')
                    ]);
                    exit;
                }
            }
            break;
            
        case 'obtenir_conseil':
            if (isset($_SESSION['user'])) {
                $donnees = chargerDonnees(DATA_FILE);
                $assistant = new AssistantComptableIA($donnees);
                $conseil = $assistant->genererConseilIntelligent();
                
                echo json_encode([
                    'success' => true,
                    'conseil' => $conseil
                ]);
                exit;
            }
            break;
            
        case 'analyser_tendances':
            if (isset($_SESSION['user'])) {
                $donnees = chargerDonnees(DATA_FILE);
                $assistant = new AssistantComptableIA($donnees);
                $analyse = $assistant->analyserTendances();
                
                echo json_encode([
                    'success' => true,
                    'analyse' => $analyse
                ]);
                exit;
            }
            break;
            
        case 'effacer_historique':
            if (isset($_SESSION['user'])) {
                $donnees = chargerDonnees(DATA_FILE);
                $donnees['chat_history'] = [];
                sauvegarderDonnees(DATA_FILE, $donnees);
                
                echo json_encode(['success' => true]);
                exit;
            }
            break;
            
        // ... autres actions existantes ...
    }
}

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Charger les données pour l'affichage
$donnees = chargerDonnees(DATA_FILE);
$categories = $donnees['categories'] ?? [];
$transactions = $donnees['transactions'] ?? [];
$budgets = $donnees['budgets'] ?? [];
$objectifs = $donnees['objectifs'] ?? [];
$factures_recurrentes = $donnees['factures_recurrentes'] ?? [];
$remboursements = $donnees['remboursements'] ?? [];
$immobilisations = $donnees['immobilisations'] ?? [];
$journaux = $donnees['journaux'] ?? ['achats' => [], 'ventes' => [], 'banque' => [], 'caisse' => []];
$comptes = $donnees['comptes'] ?? [];
$chat_history = $donnees['chat_history'] ?? [];

// Initialiser l'assistant IA
$assistant = new AssistantComptableIA($donnees);

// Calculer les totaux
$total_recettes = 0;
$total_depenses = 0;
$depenses_par_categorie = [];
$recettes_par_categorie = [];

foreach($transactions as $t) {
    if ($t['type'] === 'recette') {
        $total_recettes += $t['montant'];
        $recettes_par_categorie[$t['categorie_id']] = ($recettes_par_categorie[$t['categorie_id']] ?? 0) + $t['montant'];
    } else {
        $total_depenses += $t['montant'];
        $depenses_par_categorie[$t['categorie_id']] = ($depenses_par_categorie[$t['categorie_id']] ?? 0) + $t['montant'];
    }
}

$solde_total = $total_recettes - $total_depenses;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Comptable Pro avec IA</title>
    <style>
        /* RESET ET BASE */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* HEADER */
        header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
            z-index: 1;
        }
        
        .logo h1 {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
            z-index: 1;
        }
        
        .user-badge {
            background: rgba(255,255,255,0.15);
            padding: 10px 20px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        /* BOUTONS */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.6);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #2196F3, #0b7dda);
            color: white;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.4);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.6);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.4);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(244, 67, 54, 0.6);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #FF9800, #F57C00);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.4);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.6);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
        }
        
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(23, 162, 184, 0.6);
        }
        
        .btn-ai {
            background: linear-gradient(135deg, #9C27B0, #7B1FA2);
            color: white;
            box-shadow: 0 4px 15px rgba(156, 39, 176, 0.4);
        }
        
        .btn-ai:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(156, 39, 176, 0.6);
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        /* INTERFACE CHAT IA */
        .chat-container {
            display: flex;
            flex-direction: column;
            height: 700px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .chat-header {
            background: linear-gradient(135deg, #9C27B0, #7B1FA2);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .ai-avatar {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #9C27B0;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message {
            max-width: 80%;
            padding: 15px;
            border-radius: 15px;
            position: relative;
            animation: messageAppear 0.3s ease-out;
        }
        
        @keyframes messageAppear {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message-user {
            background: linear-gradient(135deg, #2196F3, #0b7dda);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }
        
        .message-ai {
            background: white;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .message-time {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 5px;
            text-align: right;
        }
        
        .chat-input-container {
            padding: 20px;
            background: white;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .chat-input:focus {
            border-color: #9C27B0;
            outline: none;
            box-shadow: 0 0 0 3px rgba(156, 39, 176, 0.1);
        }
        
        .quick-questions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        
        .quick-question {
            padding: 8px 15px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .quick-question:hover {
            background: #9C27B0;
            color: white;
            border-color: #9C27B0;
        }
        
        .ai-actions {
            display: flex;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }
        
        .thinking {
            display: none;
            padding: 15px;
            text-align: center;
            color: #666;
        }
        
        .thinking-dots {
            display: inline-block;
        }
        
        .thinking-dots span {
            animation: dots 1.5s infinite;
            opacity: 0;
        }
        
        .thinking-dots span:nth-child(1) { animation-delay: 0s; }
        .thinking-dots span:nth-child(2) { animation-delay: 0.2s; }
        .thinking-dots span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes dots {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }
        
        /* TABLEAUX */
        .table-container {
            padding: 30px;
            background: white;
            position: relative;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #eee;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 13px;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        /* FORMULAIRES */
        .form-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        /* NAVIGATION */
        .nav-tabs {
            display: flex;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-bottom: 2px solid #e0e0e0;
            padding: 0;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav-tab {
            padding: 20px 30px;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 4px solid transparent;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .nav-tab:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        
        .nav-tab.active {
            color: #667eea;
            background: white;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
            padding: 40px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* DASHBOARD */
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--color1), var(--color2));
        }
        
        .stat-recettes {
            --color1: #4CAF50;
            --color2: #45a049;
        }
        
        .stat-depenses {
            --color1: #f44336;
            --color2: #d32f2f;
        }
        
        .stat-solde {
            --color1: #2196F3;
            --color2: #0b7dda;
        }
        
        .stat-ai {
            --color1: #9C27B0;
            --color2: #7B1FA2;
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }
        
        .stat-card .montant {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--color1), var(--color2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .nav-tabs {
                flex-direction: column;
            }
            
            .form-container {
                padding: 20px;
                margin: 20px;
            }
            
            .chat-container {
                height: 500px;
            }
            
            .message {
                max-width: 90%;
            }
        }
        
        /* SCROLLBAR */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['user'])): ?>
    <!-- PAGE DE CONNEXION -->
    <div class="login-container" style="max-width:400px;margin:100px auto;padding:50px;background:white;border-radius:20px;text-align:center;">
        <h2>🤖 Gestion Comptable IA</h2>
        
        <?php if ($message): ?>
        <div class="message <?php echo $typeMessage; ?>" style="padding:15px;margin-bottom:20px;border-radius:8px;">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="connexion">
            
            <div style="margin-bottom:20px;">
                <label style="display:block;margin-bottom:10px;">👤 Nom d'utilisateur</label>
                <input type="text" name="username" required style="width:100%;padding:12px;border-radius:8px;border:1px solid #ddd;">
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block;margin-bottom:10px;">🔒 Mot de passe</label>
                <input type="password" name="password" required style="width:100%;padding:12px;border-radius:8px;border:1px solid #ddd;">
            </div>
            
            <button type="submit" class="btn-primary" style="width:100%;padding:15px;border-radius:8px;border:none;background:#4CAF50;color:white;font-weight:bold;">
                🤖 Se connecter
            </button>
        </form>
        
        <div style="margin-top:30px;padding:20px;background:#f8f9fa;border-radius:10px;">
            <h4 style="color:#2c3e50;margin-bottom:15px;">📋 Comptes de démonstration</h4>
            <div style="text-align:left;">
                <p><strong>Admin:</strong> admin / admin123</p>
                <p><strong>Comptable:</strong> comptable / compta123</p>
                <p><strong>Utilisateur:</strong> user / user123</p>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- INTERFACE PRINCIPALE -->
    <div class="container">
        <header>
            <div class="logo">
                <div class="logo-icon">
                    🤖
                </div>
                <div>
                    <h1>Gestion Comptable IA</h1>
                    <p style="font-size:14px;opacity:0.9;">Assistant intelligent intégré</p>
                </div>
            </div>
            
            <div class="user-info">
                <div class="user-badge">
                    👤 <?php echo htmlspecialchars($_SESSION['user']['nom']); ?> (<?php echo htmlspecialchars($_SESSION['user']['role']); ?>)
                </div>
                <a href="?logout=true" class="btn-danger" style="text-decoration:none;">
                    🚪 Déconnexion
                </a>
            </div>
        </header>
        
        <?php if ($message): ?>
        <div class="message <?php echo $typeMessage; ?>" style="margin:20px;padding:15px;border-radius:8px;">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <!-- NAVIGATION -->
        <div class="nav-tabs">
            <div class="nav-tab active" onclick="showTab('dashboard')">
                📊 Tableau de bord
            </div>
            <div class="nav-tab" onclick="showTab('assistant')">
                🤖 Assistant IA
            </div>
            <div class="nav-tab" onclick="showTab('transactions')">
                💰 Transactions
            </div>
            <div class="nav-tab" onclick="showTab('journaux')">
                📒 Journaux
            </div>
            <div class="nav-tab" onclick="showTab('immobilisations')">
                🏢 Immobilisations
            </div>
            <div class="nav-tab" onclick="showTab('amortissements')">
                📉 Amortissements
            </div>
            <div class="nav-tab" onclick="showTab('grand_livre')">
                📖 Grand Livre
            </div>
            <div class="nav-tab" onclick="showTab('balance')">
                ⚖️ Balance
            </div>
            <div class="nav-tab" onclick="showTab('bilan')">
                📄 Bilan
            </div>
            <div class="nav-tab" onclick="showTab('compte_resultat')">
                📈 Compte de résultat
            </div>
            <?php if (in_array($_SESSION['user']['role'], ['admin', 'comptable'])): ?>
            <div class="nav-tab" onclick="showTab('comptes')">
                🏦 Plan comptable
            </div>
            <?php endif; ?>
        </div>
        
        <!-- TABLEAU DE BORD -->
        <div id="dashboard" class="tab-content active">
            <div class="dashboard">
                <div class="stat-card stat-recettes">
                    <h3>Total Recettes</h3>
                    <div class="montant"><?php echo formatMontant($total_recettes); ?></div>
                    <p>💵 Montant total des recettes</p>
                </div>
                
                <div class="stat-card stat-depenses">
                    <h3>Total Dépenses</h3>
                    <div class="montant"><?php echo formatMontant($total_depenses); ?></div>
                    <p>💸 Montant total des dépenses</p>
                </div>
                
                <div class="stat-card stat-solde">
                    <h3>Solde Total</h3>
                    <div class="montant"><?php echo formatMontant($solde_total); ?></div>
                    <p>💰 Différence recettes - dépenses</p>
                </div>
                
                <div class="stat-card stat-ai">
                    <h3>Assistant IA</h3>
                    <div class="montant"><?php echo count($chat_history); ?></div>
                    <p>💬 Messages échangés</p>
                </div>
            </div>
            
            <!-- CONSEIL DE L'IA -->
            <div class="table-container">
                <h3 style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                    <span style="background:#9C27B0;color:white;padding:8px 15px;border-radius:20px;">🤖</span>
                    Conseil de l'Assistant IA
                </h3>
                <div id="conseilIa" style="padding:20px;background:linear-gradient(135deg,#f3e5f5,#e1bee7);border-radius:10px;margin-bottom:20px;">
                    <?php echo $assistant->genererConseilIntelligent(); ?>
                </div>
                <button onclick="actualiserConseil()" class="btn-ai btn-small">
                    🔄 Nouveau conseil
                </button>
            </div>
            
            <!-- ANALYSE DES TENDANCES -->
            <?php if (count($transactions) >= 10): ?>
            <div class="table-container">
                <h3 style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
                    <span style="background:#2196F3;color:white;padding:8px 15px;border-radius:20px;">📈</span>
                    Analyse des Tendances par l'IA
                </h3>
                <div id="analyseTendances" style="padding:20px;background:linear-gradient(135deg,#e3f2fd,#bbdefb);border-radius:10px;">
                    <?php echo $assistant->analyserTendances(); ?>
                </div>
                <button onclick="actualiserTendances()" class="btn-info btn-small" style="margin-top:10px;">
                    🔄 Actualiser l'analyse
                </button>
            </div>
            <?php endif; ?>
            
            <!-- DERNIÈRES TRANSACTIONS -->
            <div class="table-container">
                <h3>📋 Dernières transactions</h3>
                <?php if (empty($transactions)): ?>
                <p style="text-align:center;padding:40px;color:#666;">Aucune transaction</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Montant</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dernieres = array_slice(array_reverse($transactions), 0, 5);
                        foreach($dernieres as $t): 
                        ?>
                        <tr>
                            <td><?php echo dateFrancaise($t['date']); ?></td>
                            <td><?php echo htmlspecialchars($t['description']); ?></td>
                            <td style="color:<?php echo $t['type'] === 'recette' ? '#4CAF50' : '#F44336'; ?>;font-weight:bold;">
                                <?php echo formatMontant($t['montant']); ?>
                            </td>
                            <td>
                                <span style="padding:5px 10px;border-radius:5px;background:<?php echo $t['type'] === 'recette' ? '#4CAF50' : '#F44336'; ?>;color:white;">
                                    <?php echo $t['type'] === 'recette' ? 'Recette' : 'Dépense'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ASSISTANT IA -->
        <div id="assistant" class="tab-content">
            <div class="chat-container">
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="ai-avatar">
                            🤖
                        </div>
                        <div>
                            <h3 style="margin:0;">Assistant Comptable IA</h3>
                            <p style="margin:0;opacity:0.9;font-size:14px;">Je réponds à toutes vos questions comptables</p>
                        </div>
                    </div>
                    <button onclick="effacerHistorique()" class="btn-danger btn-small">
                        🗑️ Effacer l'historique
                    </button>
                </div>
                
                <div class="quick-questions">
                    <div class="quick-question" onclick="poserQuestion('Quel est mon solde ?')">
                        💰 Quel est mon solde ?
                    </div>
                    <div class="quick-question" onclick="poserQuestion('Combien ai-je dépensé ce mois ?')">
                        💸 Mes dépenses ce mois
                    </div>
                    <div class="quick-question" onclick="poserQuestion('Comment réduire mes dépenses ?')">
                        📉 Réduire mes dépenses
                    </div>
                    <div class="quick-question" onclick="poserQuestion('Explique-moi les amortissements')">
                        📊 Les amortissements
                    </div>
                    <div class="quick-question" onclick="poserQuestion('Que signifie TVA ?')">
                        🏛️ Qu'est-ce que la TVA ?
                    </div>
                    <div class="quick-question" onclick="poserQuestion('Comment ajouter une transaction ?')">
                        ➕ Ajouter une transaction
                    </div>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <!-- Messages chargés dynamiquement -->
                    <?php if (empty($chat_history)): ?>
                    <div class="message message-ai">
                        <div style="font-weight:bold;margin-bottom:5px;">🤖 Assistant IA</div>
                        <div>👋 Bonjour ! Je suis votre assistant comptable intelligent. Posez-moi vos questions sur vos finances, les amortissements, les budgets, ou toute autre question comptable !</div>
                        <div class="message-time"><?php echo date('H:i'); ?></div>
                    </div>
                    <?php else: ?>
                    <?php foreach($chat_history as $chat): ?>
                    <div class="message message-user">
                        <div style="font-weight:bold;margin-bottom:5px;">👤 Vous</div>
                        <div><?php echo htmlspecialchars($chat['question']); ?></div>
                        <div class="message-time"><?php echo date('H:i', strtotime($chat['timestamp'])); ?></div>
                    </div>
                    <div class="message message-ai">
                        <div style="font-weight:bold;margin-bottom:5px;">🤖 Assistant IA</div>
                        <div><?php echo nl2br(htmlspecialchars($chat['reponse'])); ?></div>
                        <div class="message-time"><?php echo date('H:i', strtotime($chat['timestamp'])); ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="thinking" id="thinkingIndicator">
                    <div class="thinking-dots">
                        <span>🤔</span>
                        <span>🤔</span>
                        <span>🤔</span>
                    </div>
                    <div>L'assistant réfléchit...</div>
                </div>
                
                <div class="ai-actions">
                    <button onclick="demanderConseil()" class="btn-ai btn-small">
                        💡 Demander un conseil
                    </button>
                    <button onclick="analyserFinances()" class="btn-info btn-small">
                        📊 Analyser mes finances
                    </button>
                </div>
                
                <div class="chat-input-container">
                    <input type="text" 
                           class="chat-input" 
                           id="questionInput" 
                           placeholder="Posez votre question comptable ici..."
                           onkeypress="if(event.key === 'Enter') envoyerQuestion()">
                    <button onclick="envoyerQuestion()" class="btn-ai">
                        📤 Envoyer
                    </button>
                </div>
            </div>
        </div>
        
        <!-- AUTRES ONGLETS (conservés tels quels) -->
        <div id="transactions" class="tab-content">
            <div class="form-container">
                <h3 style="text-align:center;margin-bottom:30px;">➕ Ajouter une transaction</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="ajouter_transaction">
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div>
                            <label>Type</label>
                            <select name="type" required>
                                <option value="recette">Recette</option>
                                <option value="depense">Dépense</option>
                            </select>
                        </div>
                        <div>
                            <label>Montant (€)</label>
                            <input type="text" name="montant" placeholder="0,00" required>
                        </div>
                    </div>
                    
                    <div>
                        <label>Description</label>
                        <input type="text" name="description" placeholder="Description de la transaction" required>
                    </div>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div>
                            <label>Date</label>
                            <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div>
                            <label>Catégorie</label>
                            <select name="categorie_id" required>
                                <?php foreach($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nom']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="width:100%;padding:15px;margin-top:20px;">
                        💾 Enregistrer la transaction
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Les autres onglets (journaux, immobilisations, etc.) restent identiques -->
        <div id="journaux" class="tab-content">
            <h3>📒 Journaux Comptables</h3>
            <p style="text-align:center;padding:40px;color:#666;">Consultez vos journaux comptables ici</p>
        </div>
        
        <div id="immobilisations" class="tab-content">
            <h3>🏢 Immobilisations</h3>
            <p style="text-align:center;padding:40px;color:#666;">Gérez vos immobilisations ici</p>
        </div>
        
        <div id="amortissements" class="tab-content">
            <h3>📉 Amortissements</h3>
            <p style="text-align:center;padding:40px;color:#666;">Calculez vos amortissements ici</p>
        </div>
        
        <div id="grand_livre" class="tab-content">
            <h3>📖 Grand Livre</h3>
            <p style="text-align:center;padding:40px;color:#666;">Consultez le grand livre ici</p>
        </div>
        
        <div id="balance" class="tab-content">
            <h3>⚖️ Balance</h3>
            <p style="text-align:center;padding:40px;color:#666;">Générez la balance ici</p>
        </div>
        
        <div id="bilan" class="tab-content">
            <h3>📄 Bilan</h3>
            <p style="text-align:center;padding:40px;color:#666;">Générez le bilan ici</p>
        </div>
        
        <div id="compte_resultat" class="tab-content">
            <h3>📈 Compte de résultat</h3>
            <p style="text-align:center;padding:40px;color:#666;">Générez le compte de résultat ici</p>
        </div>
        
        <?php if (in_array($_SESSION['user']['role'], ['admin', 'comptable'])): ?>
        <div id="comptes" class="tab-content">
            <h3>🏦 Plan Comptable</h3>
            <p style="text-align:center;padding:40px;color:#666;">Consultez le plan comptable ici</p>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Variables globales
        let chatMessages = document.getElementById('chatMessages');
        let questionInput = document.getElementById('questionInput');
        let thinkingIndicator = document.getElementById('thinkingIndicator');
        
        // Fonction pour afficher un onglet
        function showTab(tabId) {
            // Masquer tous les onglets
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Désactiver tous les onglets
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Afficher l'onglet sélectionné
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
            
            // Si on passe à l'onglet assistant, scroll en bas
            if (tabId === 'assistant') {
                setTimeout(() => {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }, 100);
            }
        }
        
        // Fonction pour envoyer une question
        function envoyerQuestion() {
            const question = questionInput.value.trim();
            if (!question) return;
            
            // Ajouter le message de l'utilisateur
            ajouterMessageUtilisateur(question);
            questionInput.value = '';
            
            // Afficher l'indicateur de pensée
            thinkingIndicator.style.display = 'block';
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Envoyer la question au serveur
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=poser_question&question=' + encodeURIComponent(question)
            })
            .then(response => response.json())
            .then(data => {
                thinkingIndicator.style.display = 'none';
                if (data.success) {
                    ajouterMessageAI(data.reponse, data.timestamp);
                } else {
                    ajouterMessageAI("❌ Désolé, une erreur s'est produite. Veuillez réessayer.");
                }
            })
            .catch(error => {
                thinkingIndicator.style.display = 'none';
                ajouterMessageAI("❌ Erreur de connexion. Vérifiez votre réseau.");
            });
        }
        
        // Fonction pour poser une question prédéfinie
        function poserQuestion(question) {
            questionInput.value = question;
            envoyerQuestion();
        }
        
        // Fonction pour ajouter un message utilisateur
        function ajouterMessageUtilisateur(texte) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message message-user';
            messageDiv.innerHTML = `
                <div style="font-weight:bold;margin-bottom:5px;">👤 Vous</div>
                <div>${escapeHtml(texte)}</div>
                <div class="message-time">${new Date().toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'})}</div>
            `;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Fonction pour ajouter un message AI
        function ajouterMessageAI(texte, timestamp = null) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message message-ai';
            messageDiv.innerHTML = `
                <div style="font-weight:bold;margin-bottom:5px;">🤖 Assistant IA</div>
                <div>${formatText(texte)}</div>
                <div class="message-time">${timestamp || new Date().toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'})}</div>
            `;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Fonction pour demander un conseil
        function demanderConseil() {
            poserQuestion("Donne-moi un conseil pour améliorer mes finances");
        }
        
        // Fonction pour analyser les finances
        function analyserFinances() {
            poserQuestion("Analyse mes finances et donne-moi des recommandations");
        }
        
        // Fonction pour actualiser le conseil
        function actualiserConseil() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=obtenir_conseil'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('conseilIa').innerHTML = formatText(data.conseil);
                }
            });
        }
        
        // Fonction pour actualiser les tendances
        function actualiserTendances() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=analyser_tendances'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('analyseTendances').innerHTML = formatText(data.analyse);
                }
            });
        }
        
        // Fonction pour effacer l'historique
        function effacerHistorique() {
            if (confirm("Voulez-vous vraiment effacer tout l'historique de conversation ?")) {
                fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=effacer_historique'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        chatMessages.innerHTML = `
                            <div class="message message-ai">
                                <div style="font-weight:bold;margin-bottom:5px;">🤖 Assistant IA</div>
                                <div>👋 Historique effacé ! Bonjour ! Je suis votre assistant comptable intelligent. Posez-moi vos questions sur vos finances !</div>
                                <div class="message-time">${new Date().toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'})}</div>
                            </div>
                        `;
                    }
                });
            }
        }
        
        // Fonction pour formater le texte (gras, sauts de ligne)
        function formatText(text) {
            // Convertir les **texte** en <strong>texte</strong>
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            
            // Convertir les sauts de ligne en <br>
            text = text.replace(/\n/g, '<br>');
            
            // Échapper le HTML restant
            return escapeHtml(text).replace(/&lt;br&gt;/g, '<br>').replace(/&lt;strong&gt;/g, '<strong>').replace(/&lt;\/strong&gt;/g, '</strong>');
        }
        
        // Fonction pour échapper le HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Raccourci clavier Enter pour envoyer
        questionInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                envoyerQuestion();
            }
        });
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll en bas du chat
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Raccourcis clavier
            document.addEventListener('keydown', function(e) {
                // Ctrl + / : Focus sur le chat
                if (e.ctrlKey && e.key === '/') {
                    e.preventDefault();
                    questionInput.focus();
                }
                
                // Échap : Vider le champ
                if (e.key === 'Escape') {
                    questionInput.value = '';
                }
            });
            
            // Focus sur le champ de question quand on ouvre l'onglet assistant
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    if (this.textContent.includes('Assistant IA')) {
                        setTimeout(() => {
                            questionInput.focus();
                        }, 300);
                    }
                });
            });
        });
    </script>
    </body>
</html>
<?php endif; ?>