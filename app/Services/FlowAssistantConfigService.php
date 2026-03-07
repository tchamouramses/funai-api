<?php

namespace App\Services;

class FlowAssistantConfigService
{
    /**
     * Map flow type → assistant_type key used in SharedAssistant
     */
    public static function getAssistantType(string $flowType): string
    {
        return match ($flowType) {
            'todo' => 'flow_todo',
            'fitness' => 'flow_fitness',
            'nutrition' => 'flow_nutrition',
            'finance' => 'flow_finance',
            default => 'flow_todo',
        };
    }

    /**
     * Build the OpenAI assistant config for a given flow type
     */
    public function buildConfig(string $flowType): array
    {
        return match ($flowType) {
            'fitness' => $this->buildFitnessConfig(),
            'nutrition' => $this->buildNutritionConfig(),
            'finance' => $this->buildFinanceConfig(),
            default => $this->buildTodoConfig(),
        };
    }

    /**
     * Build config for the general/overview assistant (listing view)
     */
    public function buildGeneralConfig(): array
    {
        return [
            'model' => 'gpt-4o',
            'name' => 'FunAI Flow Manager',
            'instructions' => <<<'TEXT'
Tu es FunAI, un assistant intelligent de gestion de flows (listes organisées).
Tu aides l'utilisateur à gérer tous ses flows : créer de nouveaux flows, consulter leurs statistiques, les organiser.

Tu as accès à des outils pour :
- Créer des flows (todo, fitness, nutrition, finance)
- Lister tous les flows de l'utilisateur
- Obtenir des statistiques globales
- Rechercher dans les flows

Sois proactif : si l'utilisateur décrit un besoin, propose le type de flow adapté.

IMPORTANT: Formate tes réponses en markdown.
TEXT,
            'tools' => $this->getGeneralTools(),
        ];
    }

    // ─── TODO ────────────────────────────────────────────────────────────

    private function buildTodoConfig(): array
    {
        return [
            'model' => 'gpt-4o',
            'name' => 'FunAI Todo Assistant',
            'instructions' => <<<'TEXT'
Tu es FunAI, un assistant spécialisé dans la gestion des listes de tâches et projets.
Tu aides l'utilisateur à créer, modifier et organiser ses tâches et sous-listes.

Tu peux :
- Créer et supprimer des sous-listes
- Ajouter, modifier, compléter et supprimer des tâches
- Définir des dates d'échéance et récurrences
- Chercher des tâches et trouver celles en retard
- Afficher les statistiques de progression

Quand l'utilisateur décrit des tâches, crée-les automatiquement avec les bons outils.
Demande confirmation avant de supprimer.

IMPORTANT: Formate tes réponses en markdown.
TEXT,
            'tools' => $this->getTodoTools(),
        ];
    }

    // ─── FITNESS ─────────────────────────────────────────────────────────

    private function buildFitnessConfig(): array
    {
        return [
            'model' => 'gpt-4o',
            'name' => 'FunAI Fitness Coach',
            'instructions' => <<<'TEXT'
Tu es FunAI, un coach sportif IA spécialisé dans la gestion de programmes d'entraînement.
Tu aides l'utilisateur à gérer son flow fitness : exercices, séries, récupération, défis.

Tu peux :
- Ajouter, modifier et supprimer des exercices
- Marquer des exercices comme complétés
- Créer des sous-listes (jours, groupes musculaires)
- Consulter le dashboard fitness (streaks, progression)
- Gérer les défis (challenges)
- Générer un programme d'entraînement complet

Sois motivant et propose des améliorations basées sur la progression.

IMPORTANT: Formate tes réponses en markdown.
TEXT,
            'tools' => $this->getFitnessTools(),
        ];
    }

    // ─── NUTRITION ───────────────────────────────────────────────────────

    private function buildNutritionConfig(): array
    {
        return [
            'model' => 'gpt-4o',
            'name' => 'FunAI Nutrition Assistant',
            'instructions' => <<<'TEXT'
Tu es FunAI, un assistant nutritionnel IA spécialisé dans le suivi alimentaire.
Tu aides l'utilisateur à gérer son flow nutrition : repas, aliments, apports caloriques.

Tu peux :
- Ajouter des repas et aliments avec leurs informations nutritionnelles
- Modifier et supprimer des entrées
- Consulter le dashboard nutritionnel
- Créer des sous-listes (jours, types de repas)
- Calculer les apports journaliers
- Suggérer des repas équilibrés

IMPORTANT: Tu n'es pas nutritionniste certifié. Rappelle à l'utilisateur de consulter un professionnel pour des régimes spécifiques.

IMPORTANT: Formate tes réponses en markdown.
TEXT,
            'tools' => $this->getNutritionTools(),
        ];
    }

    // ─── FINANCE ─────────────────────────────────────────────────────────

    private function buildFinanceConfig(): array
    {
        return [
            'model' => 'gpt-4o',
            'name' => 'FunAI Finance Assistant',
            'instructions' => <<<'TEXT'
Tu es FunAI, un assistant financier IA spécialisé dans la gestion budgétaire.
Tu aides l'utilisateur à gérer son flow finance : transactions, budgets, catégories.

Tu peux :
- Ajouter, modifier et supprimer des transactions (revenus et dépenses)
- Gérer les budgets par catégorie
- Consulter le dashboard financier
- Gérer les catégories personnalisées
- Analyser les tendances de dépenses
- Créer des sous-listes (comptes, projets financiers)

IMPORTANT: Tu n'es pas conseiller financier certifié. Rappelle à l'utilisateur de consulter un professionnel pour des décisions financières importantes.

IMPORTANT: Formate tes réponses en markdown.
TEXT,
            'tools' => $this->getFinanceTools(),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  TOOLS DEFINITIONS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Common list-management tools shared by all flow types
     */
    private function getCommonListTools(): array
    {
        return [
            $this->fn('create_sublist', 'Crée une sous-liste dans le flow actuel', [
                'title' => ['type' => 'string', 'description' => 'Titre de la sous-liste'],
                'description' => ['type' => 'string', 'description' => 'Description optionnelle'],
                'items' => [
                    'type' => 'array',
                    'description' => 'Tâches initiales',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'content' => ['type' => 'string', 'description' => 'Contenu'],
                            'dueDate' => ['type' => 'string', 'description' => "Date d'échéance ISO"],
                        ],
                        'required' => ['content'],
                    ],
                ],
            ], ['title']),

            $this->fn('get_sublists', 'Récupère les sous-listes du flow actuel', [
                'listId' => ['type' => 'string', 'description' => 'ID (optionnel si flow ouvert)'],
            ]),

            $this->fn('add_task', 'Ajoute une ou plusieurs tâches au flow ou à une sous-liste', [
                'listId' => ['type' => 'string', 'description' => 'ID de la liste (optionnel)'],
                'tasks' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'dueDate' => ['type' => 'string'],
                            'metadata' => ['type' => 'object', 'description' => 'Métadonnées spécifiques'],
                        ],
                        'required' => ['title'],
                    ],
                    'description' => 'Tableau de tâches',
                ],
            ], ['tasks']),

            $this->fn('update_task', 'Met à jour une tâche existante', [
                'listId' => ['type' => 'string', 'description' => 'ID de la liste'],
                'taskId' => ['type' => 'string', 'description' => 'ID de la tâche'],
                'title' => ['type' => 'string', 'description' => 'Nouveau titre'],
                'description' => ['type' => 'string', 'description' => 'Nouvelle description'],
                'completed' => ['type' => 'boolean', 'description' => 'État'],
            ], ['listId', 'taskId']),

            $this->fn('delete_task', 'Supprime une tâche', [
                'listId' => ['type' => 'string', 'description' => 'ID de la liste'],
                'taskId' => ['type' => 'string', 'description' => 'ID de la tâche'],
            ], ['listId', 'taskId']),

            $this->fn('complete_task', 'Marque une tâche comme complétée ou non', [
                'listId' => ['type' => 'string', 'description' => 'ID de la liste'],
                'taskId' => ['type' => 'string', 'description' => 'ID de la tâche'],
                'completed' => ['type' => 'boolean', 'description' => 'État'],
            ], ['listId', 'taskId', 'completed']),

            $this->fn('get_list_tasks', 'Récupère toutes les tâches d\'une liste', [
                'listId' => ['type' => 'string', 'description' => 'ID de la liste'],
            ], ['listId']),

            $this->fn('get_list_details', 'Récupère les détails d\'une liste', [
                'listId' => ['type' => 'string', 'description' => 'ID (optionnel)'],
            ]),

            $this->fn('set_due_date', 'Définit une échéance', [
                'listId' => ['type' => 'string', 'description' => 'ID de la liste'],
                'taskId' => ['type' => 'string', 'description' => 'ID de la tâche (optionnel)'],
                'dueDate' => ['type' => 'string', 'description' => "Date ISO"],
            ], ['listId', 'dueDate']),

            $this->fn('search_tasks', 'Recherche des tâches par mot-clé', [
                'query' => ['type' => 'string', 'description' => 'Terme de recherche'],
                'listId' => ['type' => 'string', 'description' => 'ID de la liste (optionnel)'],
            ], ['query']),

            $this->fn('find_overdue_tasks', 'Trouve les tâches en retard', [
                'listId' => ['type' => 'string', 'description' => 'ID (optionnel)'],
            ]),

            $this->fn('get_statistics', 'Obtient les statistiques du flow', [
                'listId' => ['type' => 'string', 'description' => 'ID (optionnel)'],
            ]),

            $this->fn('update_list', 'Met à jour le titre ou la description du flow ou sous-liste', [
                'listId' => ['type' => 'string', 'description' => 'ID (optionnel)'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
            ], ['listId']),

            $this->fn('delete_list', 'Supprime un flow ou sous-liste', [
                'listId' => ['type' => 'string', 'description' => 'ID (optionnel)'],
            ]),
        ];
    }

    // ─── General tools ───────────────────────────────────────────────────

    private function getGeneralTools(): array
    {
        return [
            $this->fn('create_list', 'Crée un nouveau flow', [
                'title' => ['type' => 'string', 'description' => 'Titre du flow'],
                'description' => ['type' => 'string', 'description' => 'Description'],
                'type' => [
                    'type' => 'string',
                    'enum' => ['todo', 'fitness', 'nutrition', 'finance'],
                    'description' => 'Type de flow',
                ],
                'items' => [
                    'type' => 'array',
                    'description' => 'Tâches initiales',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'content' => ['type' => 'string'],
                            'dueDate' => ['type' => 'string'],
                        ],
                        'required' => ['content'],
                    ],
                ],
            ], ['title', 'type']),

            $this->fn('list_all_lists', 'Liste tous les flows de l\'utilisateur', (object) []),

            $this->fn('get_statistics', 'Obtient les statistiques globales', [
                'listId' => ['type' => 'string', 'description' => 'ID (optionnel)'],
            ]),

            $this->fn('search_tasks', 'Recherche dans tous les flows', [
                'query' => ['type' => 'string', 'description' => 'Terme de recherche'],
                'listId' => ['type' => 'string', 'description' => 'ID (optionnel)'],
            ], ['query']),

            $this->fn('find_overdue_tasks', 'Trouve les tâches en retard dans tous les flows', [
                'listId' => ['type' => 'string', 'description' => 'ID (optionnel)'],
            ]),

            $this->fn('get_tasks_by_date', 'Récupère les tâches pour une date', [
                'dateType' => [
                    'type' => 'string',
                    'enum' => ['today', 'tomorrow', 'specific'],
                    'description' => 'Type de date',
                ],
                'date' => ['type' => 'string', 'description' => 'Date ISO (pour specific)'],
            ], ['dateType']),
        ];
    }

    // ─── Todo tools ──────────────────────────────────────────────────────

    private function getTodoTools(): array
    {
        return array_merge($this->getCommonListTools(), [
            $this->fn('set_recurrence', 'Définit une récurrence pour une liste', [
                'listId' => ['type' => 'string', 'description' => 'ID de la liste'],
                'frequency' => [
                    'type' => 'string',
                    'enum' => ['daily', 'weekly', 'monthly'],
                    'description' => 'Fréquence',
                ],
                'interval' => ['type' => 'number', 'description' => 'Intervalle'],
            ], ['listId', 'frequency', 'interval']),

            $this->fn('get_tasks_by_date', 'Récupère les tâches pour une date', [
                'dateType' => [
                    'type' => 'string',
                    'enum' => ['today', 'tomorrow', 'specific'],
                    'description' => 'Type de date',
                ],
                'date' => ['type' => 'string', 'description' => 'Date ISO'],
            ], ['dateType']),
        ]);
    }

    // ─── Fitness tools ───────────────────────────────────────────────────

    private function getFitnessTools(): array
    {
        return array_merge($this->getCommonListTools(), [
            $this->fn('get_fitness_dashboard', 'Récupère le dashboard fitness (streaks, progression, activité)', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow fitness'],
            ], ['listId']),

            $this->fn('get_fitness_challenges', 'Liste les défis fitness actifs et complétés', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow fitness'],
            ], ['listId']),

            $this->fn('create_fitness_challenge', 'Crée un nouveau défi fitness', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow fitness'],
                'type' => [
                    'type' => 'string',
                    'enum' => ['streak', 'volume', 'perfect_week', 'endurance', 'strength', 'consistency'],
                    'description' => 'Type de défi',
                ],
                'title' => ['type' => 'string', 'description' => 'Titre du défi'],
                'description' => ['type' => 'string', 'description' => 'Description'],
                'targetValue' => ['type' => 'number', 'description' => 'Valeur cible'],
                'durationDays' => ['type' => 'number', 'description' => 'Durée en jours'],
            ], ['listId', 'type', 'title', 'targetValue', 'durationDays']),

            $this->fn('log_exercise_progress', 'Enregistre la progression d\'un exercice (sets, reps, poids)', [
                'itemId' => ['type' => 'string', 'description' => 'ID de l\'exercice'],
                'value' => ['type' => 'number', 'description' => 'Valeur (poids, reps, etc.)'],
                'notes' => ['type' => 'string', 'description' => 'Notes optionnelles'],
            ], ['itemId', 'value']),

            $this->fn('generate_workout_program', 'Génère un programme d\'entraînement complet via IA', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow fitness'],
                'goal' => [
                    'type' => 'string',
                    'enum' => ['weight_loss', 'muscle_gain', 'endurance', 'strength', 'flexibility', 'general_fitness'],
                    'description' => 'Objectif',
                ],
                'level' => [
                    'type' => 'string',
                    'enum' => ['beginner', 'intermediate', 'advanced'],
                    'description' => 'Niveau',
                ],
                'daysPerWeek' => ['type' => 'number', 'description' => 'Nombre de jours par semaine'],
            ], ['listId', 'goal', 'level', 'daysPerWeek']),
        ]);
    }

    // ─── Nutrition tools ─────────────────────────────────────────────────

    private function getNutritionTools(): array
    {
        return array_merge($this->getCommonListTools(), [
            $this->fn('get_nutrition_dashboard', 'Récupère le dashboard nutritionnel', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow nutrition'],
            ], ['listId']),

            $this->fn('add_meal', 'Ajoute un repas avec ses aliments et informations nutritionnelles', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow nutrition'],
                'mealType' => [
                    'type' => 'string',
                    'enum' => ['breakfast', 'lunch', 'dinner', 'snack'],
                    'description' => 'Type de repas',
                ],
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string', 'description' => 'Nom de l\'aliment'],
                            'calories' => ['type' => 'number', 'description' => 'Calories'],
                            'protein' => ['type' => 'number', 'description' => 'Protéines (g)'],
                            'carbs' => ['type' => 'number', 'description' => 'Glucides (g)'],
                            'fat' => ['type' => 'number', 'description' => 'Lipides (g)'],
                            'quantity' => ['type' => 'string', 'description' => 'Quantité (ex: 100g, 1 portion)'],
                        ],
                        'required' => ['name'],
                    ],
                    'description' => 'Aliments du repas',
                ],
            ], ['listId', 'mealType', 'items']),

            $this->fn('get_daily_intake', 'Calcule les apports nutritionnels du jour', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow nutrition'],
                'date' => ['type' => 'string', 'description' => 'Date ISO (optionnel, par défaut aujourd\'hui)'],
            ], ['listId']),

            $this->fn('suggest_meal', 'Suggère un repas équilibré basé sur les objectifs et apports restants', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow nutrition'],
                'mealType' => [
                    'type' => 'string',
                    'enum' => ['breakfast', 'lunch', 'dinner', 'snack'],
                    'description' => 'Type de repas',
                ],
                'preferences' => ['type' => 'string', 'description' => 'Préférences alimentaires (optionnel)'],
            ], ['listId', 'mealType']),
        ]);
    }

    // ─── Finance tools ───────────────────────────────────────────────────

    private function getFinanceTools(): array
    {
        return array_merge($this->getCommonListTools(), [
            $this->fn('get_finance_dashboard', 'Récupère le dashboard financier', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow finance'],
            ], ['listId']),

            $this->fn('add_transaction', 'Ajoute une transaction (revenu ou dépense)', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow finance'],
                'type' => [
                    'type' => 'string',
                    'enum' => ['income', 'expense'],
                    'description' => 'Type de transaction',
                ],
                'amount' => ['type' => 'number', 'description' => 'Montant'],
                'category' => ['type' => 'string', 'description' => 'Catégorie'],
                'description' => ['type' => 'string', 'description' => 'Description'],
                'date' => ['type' => 'string', 'description' => 'Date ISO'],
                'paymentMethod' => ['type' => 'string', 'description' => 'Méthode de paiement'],
            ], ['listId', 'type', 'amount', 'category']),

            $this->fn('get_transactions', 'Liste les transactions avec filtres optionnels', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow finance'],
                'type' => ['type' => 'string', 'enum' => ['income', 'expense'], 'description' => 'Filtrer par type'],
                'category' => ['type' => 'string', 'description' => 'Filtrer par catégorie'],
                'startDate' => ['type' => 'string', 'description' => 'Date de début ISO'],
                'endDate' => ['type' => 'string', 'description' => 'Date de fin ISO'],
            ], ['listId']),

            $this->fn('create_budget', 'Crée un budget pour une catégorie', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow finance'],
                'category' => ['type' => 'string', 'description' => 'Catégorie'],
                'amount' => ['type' => 'number', 'description' => 'Montant du budget'],
                'period' => [
                    'type' => 'string',
                    'enum' => ['weekly', 'monthly', 'yearly'],
                    'description' => 'Période',
                ],
            ], ['listId', 'category', 'amount', 'period']),

            $this->fn('get_budgets', 'Liste les budgets du flow finance', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow finance'],
            ], ['listId']),

            $this->fn('get_categories', 'Liste les catégories de transactions', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow finance'],
            ], ['listId']),

            $this->fn('analyze_spending', 'Analyse les tendances de dépenses', [
                'listId' => ['type' => 'string', 'description' => 'ID du flow finance'],
                'period' => [
                    'type' => 'string',
                    'enum' => ['week', 'month', 'year'],
                    'description' => 'Période d\'analyse',
                ],
            ], ['listId']),
        ]);
    }

    // ─── Helper ──────────────────────────────────────────────────────────

    /**
     * Build a function tool definition with less boilerplate
     */
    private function fn(string $name, string $description, array|object $properties, array $required = []): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => $properties,
                    'required' => $required,
                ],
            ],
        ];
    }
}
