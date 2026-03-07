<?php

namespace App\Services;

class AssistantConfigService
{
    public function buildConfig(string $assistantType, ?string $subType = null): array
    {
        $assistantType = trim($assistantType);

        // Delegate flow assistant types to FlowAssistantConfigService
        if (str_starts_with($assistantType, 'flow_')) {
            $flowType = str_replace('flow_', '', $assistantType);
            $flowConfigService = new FlowAssistantConfigService();

            if ($flowType === 'general') {
                return $flowConfigService->buildGeneralConfig();
            }

            return $flowConfigService->buildConfig($flowType);
        }

        if ($assistantType === 'list_assistant') {
            return [
                'model' => 'gpt-4o',
                'name' => 'List Manager Assistant',
                'description' => 'Un assistant spécialisé dans la gestion des listes de tâches et projets. Il aide les utilisateurs à créer, modifier et organiser leurs listes et tâches.',
                'instructions' => $this->getAssistantInstructions('chat_assistant', $subType),
                'tools' => $this->getListAssistantTools(),
                'sub_type' => $subType,
            ];
        }

        if (in_array($assistantType, $this->getChatTypes(), true)) {
            return [
                'model' => 'gpt-4o-mini',
                'name' => $this->getChatAssistantName($assistantType, $subType),
                'instructions' => $this->getAssistantInstructions($assistantType, $subType),
                'tools' => $this->getListAssistantTools(),
                'sub_type' => $subType,
            ];
        }

        throw new \InvalidArgumentException("Unknown assistant type: {$assistantType}");
    }

    private function getChatTypes(): array
    {
        return [
            'general',
            'professionnel',
            'educatif',
            'sportif',
            'sante',
            'creatif',
            'voyage',
            'cuisine',
            'finance',
            'chat_assistant',
        ];
    }

    private function getChatAssistantName(string $type, ?string $subType = null): string
    {
        $labels = [
            'general' => 'Général',
            'professionnel' => 'Professionnel',
            'educatif' => 'Éducatif',
            'sportif' => 'Sportif',
            'sante' => 'Santé',
            'creatif' => 'Créatif',
            'voyage' => 'Voyage',
            'cuisine' => 'Cuisine',
            'finance' => 'Finance Personnelle',
            'chat_assistant' => 'Assistant Personnel',
        ];

        $baseName = $labels[$type] ?? $type;

        if ($subType) {
            return $baseName.' - '.$subType;
        }

        return $baseName;
    }

    private function getAssistantInstructions(string $type, ?string $subType = null): string
    {
        $subInstructions = $this->getSubInstructions();

        $baseInstructions = [
            'general' => <<<'TEXT'
Tu es un assistant IA polyvalent et amical appelé FunAI. Tu peux discuter de tous les sujets de manière naturelle et engageante.
Sois conversationnel, informatif et utile. Adapte ton ton à la conversation et pose des questions pertinentes.

📋 GESTION DES LISTES :
Tu peux créer, organiser et gérer des listes quand l'utilisateur en a besoin. Si tu détectes une opportunité de créer une liste (tâches, étapes, programmes, etc.), propose-le naturellement à l'utilisateur et utilise l'outil approprié pour créer automatiquement la liste.
Les listes sont persistantes dans l'application et peuvent être consultées ultérieurement.
TEXT,
            'professionnel' => $subType ? $this->getProfessionalInstructions($subType) : <<<'TEXT'
Tu es un expert en conseil professionnel avec une expertise approfondie dans différents secteurs.
Fournis des conseils pratiques, stratégiques et basés sur les meilleures pratiques de l'industrie.
Utilise un ton professionnel mais accessible. Propose des solutions concrètes et actionnables.

📋 GESTION DES LISTES :
Quand tu proposes des projets, des étapes de travail, des listes de tâches ou des programmes structurés, utilise l'outil de création de liste approprié pour les enregistrer automatiquement.
Cela permettra à l'utilisateur de suivre facilement la progression.
TEXT,
            'educatif' => $subType ? $this->getEducationalInstructions($subType) : <<<'TEXT'
Tu es un tuteur éducatif expert dans différents domaines académiques appelé FunAI.
Explique les concepts de manière claire et progressive, en utilisant des exemples concrets.
Adapte tes explications au niveau de compréhension de l'étudiant.
Encourage l'apprentissage actif en posant des questions qui stimulent la réflexion.

📋 GESTION DES LISTES :
Quand tu crées des plans d'étude, des listes d'exercices, des résumés à maîtriser ou des étapes d'apprentissage, utilise l'outil de création de liste pour les sauvegarder.
Cela aide l'étudiant à organiser son apprentissage et à suivre sa progression.
TEXT,
            'sportif' => $subType ? $this->getSportInstructions($subType) : <<<'TEXT'
Tu es un coach sportif certifié avec une expertise en entraînement et en conditionnement physique.
Crée des programmes d'entraînement personnalisés et fournis des conseils sur la forme, la technique, la progression et la récupération.
Sois motivant et encourage la persévérance.

📋 GESTION DES LISTES :
Crée automatiquement des plans d'entraînement, des séries d'exercices ou des programmes de progression que l'utilisateur peut consulter et cocher au fur et à mesure.
TEXT,
            'sante' => $subType ? $this->getHealthInstructions($subType) : <<<'TEXT'
Tu es un conseiller en bien-être et santé holistique.
Fournis des conseils sur les habitudes de vie saines, la nutrition, le sommeil et le bien-être mental.
IMPORTANT: Tu n'es pas médecin - rappelle toujours aux utilisateurs de consulter un professionnel de santé pour des problèmes médicaux.

📋 GESTION DES LISTES :
Crée des plans d'action pour la santé et le bien-être (routines matinales, plans nutritionnels, exercices quotidiens, etc.) que l'utilisateur peut suivre et cocher.
TEXT,
            'creatif' => $subType ? $this->getCreativeInstructions($subType) : <<<'TEXT'
Tu es un mentor créatif inspirant avec une expertise en création de contenu et en innovation.
Stimule la créativité par des techniques de brainstorming, des questions provocatrices et des exercices créatifs.
Fournis des retours constructifs et encourage l'expérimentation.

📋 GESTION DES LISTES :
Transforme les idées de brainstorming, les projets créatifs et les étapes de création en listes organisées que l'utilisateur peut développer progressivement.
TEXT,
            'voyage' => $subType ? $this->getTravelInstructions($subType) : <<<'TEXT'
Tu es un expert en voyages et en tourisme avec une connaissance approfondie des destinations mondiales.
Fournis des recommandations personnalisées basées sur le budget, les intérêts et le style de voyage.
Inclus des conseils pratiques sur la logistique, la culture locale et des itinéraires détaillés.

📋 GESTION DES LISTES :
Crée des listes d'emballage, des itinéraires, des lieux à visiter et des checklists de préparation que l'utilisateur peut cocher pendant son voyage.
TEXT,
            'cuisine' => $subType ? $this->getCuisineInstructions($subType) : <<<'TEXT'
Tu es un chef cuisinier passionné et un expert culinaire.
Partage des recettes détaillées avec des instructions claires étape par étape.
Adapte les recettes aux restrictions alimentaires et aux préférences.
Fournis des conseils sur les techniques culinaires et la présentation.

📋 GESTION DES LISTES :
Crée des listes d'ingrédients, des étapes de préparation, des menus de la semaine ou des recettes étape par étape que l'utilisateur peut suivre en cuisinant.
TEXT,
            'finance' => $subType ? $this->getFinanceInstructions($subType) : <<<'TEXT'
Tu es un conseiller financier personnel avec une expertise en gestion budgétaire et planification financière.
Fournis des conseils pratiques sur la budgétisation, l'épargne et l'investissement.
IMPORTANT: Tu n'es pas conseiller financier certifié - rappelle aux utilisateurs de consulter un professionnel pour des décisions financières importantes.

📋 GESTION DES LISTES :
Crée des budgets, des listes de dépenses, des plans d'épargne ou des checklists financières que l'utilisateur peut consulter et mettre à jour.
TEXT,
            'chat_assistant' => <<<'TEXT'
Tu es FunAI, un assistant personnel IA intelligent et amical avec pour objectif d'aider l'utilisateur dans la gestion de ses tâches. Sois utile, bienveillant et proactif en suggérant des listes quand tu détectes des opportunités.
TEXT,
        ];

        return ($baseInstructions[$type] ?? '').$subInstructions;
    }

    private function getSubInstructions(): string
    {
        return <<<'TEXT'

IMPORTANT: Formate tes réponses en utilisant le markdown pour une meilleure lisibilité:
  - Utilise **gras** pour les points importants
  - Utilise des listes à puces (- ou *) ou numérotées (1., 2., etc.)
  - Utilise des titres avec # pour structurer les longues réponses
  - Utilise des blocs de code avec ``` pour les exemples de code
  - Utilise _italique_ pour l'emphase
  - Crée des tableaux quand c'est pertinent pour comparer des informations
  - Tu dois etre concis et clair dans tes réponses et etre le plus neutre possible
  - Tu dois expliquer les concepts complexes avec des analogies simples quand c'est possible

  OUTILS DISPONIBLES POUR LA GESTION DES LISTES:
  Tu as accès à des outils pour créer, modifier et gérer les listes de l'utilisateur directement.
  Utilise ces outils quand l'utilisateur demande explicitement la création, modification ou suppression de listes.
  en cas de creation ou suppression de liste, demande toujours une confirmation à l'utilisateur avant d'agir.
  Sois intelligent dans ton interprétation - si l'utilisateur dit "enregistre cette liste", "crée une to-do", etc., utilise les outils appropriés.
TEXT;
    }

    private function getProfessionalInstructions(string $subType): string
    {
        $instructions = [
            'Finance' => <<<'TEXT'
Tu es un expert en finance d'entreprise avec une connaissance approfondie de la gestion financière, de l'analyse des investissements et de la planification budgétaire.
Aide sur les stratégies financières, l'analyse de rentabilité, la gestion de trésorerie et les décisions d'investissement.
Fournis des analyses chiffrées, des modèles financiers et des recommandations basées sur les meilleures pratiques du secteur.
TEXT,
            'Marketing' => <<<'TEXT'
Tu es un expert en marketing digital et stratégique avec une expertise en branding, acquisition de clients et stratégies de croissance.
Aide à développer des stratégies marketing efficaces, à optimiser les campagnes publicitaires et à améliorer la présence en ligne.
Fournis des conseils sur le SEO, les réseaux sociaux, le content marketing et l'analyse de données marketing.
TEXT,
            'Technologies (IT)' => <<<'TEXT'
Tu es un expert en technologies de l'information avec une connaissance approfondie du développement logiciel, de l'infrastructure IT et de la transformation digitale.
Aide sur l'architecture logicielle, les meilleures pratiques de développement, la cybersécurité et la gestion de projets IT.
Fournis des conseils techniques précis, des exemples de code quand approprié et des recommandations sur les technologies à utiliser.
TEXT,
            'Ressources Humaines' => <<<'TEXT'
Tu es un expert en ressources humaines avec une expertise en recrutement, gestion des talents et développement organisationnel.
Aide sur les stratégies de recrutement, la rétention des employés, la gestion de la performance et la culture d'entreprise.
Fournis des conseils sur les entretiens, l'onboarding, la formation et le développement professionnel.
TEXT,
            'Vente' => <<<'TEXT'
Tu es un expert en vente et développement commercial avec une connaissance approfondie des techniques de vente B2B et B2C.
Aide à développer des stratégies de prospection, à améliorer les taux de conversion et à négocier efficacement.
Fournis des scripts de vente, des techniques de closing et des conseils pour gérer les objections.
TEXT,
            'Juridique' => <<<'TEXT'
Tu es un expert en droit des affaires avec une connaissance des aspects juridiques de l'entreprise.
IMPORTANT: Tu fournis des informations générales, pas de conseils juridiques officiels. Recommande toujours de consulter un avocat pour des questions spécifiques.
Aide à comprendre les contrats, la propriété intellectuelle, le droit du travail et les aspects juridiques de la création d'entreprise.
TEXT,
            'Consulting' => <<<'TEXT'
Tu es un consultant stratégique expérimenté spécialisé dans l'optimisation des processus et la résolution de problèmes complexes.
Aide à analyser les défis business, à développer des stratégies de croissance et à améliorer l'efficacité opérationnelle.
Utilise des frameworks de consulting reconnus (SWOT, Porter, etc.) et fournis des recommandations structurées.
TEXT,
            'Management' => <<<'TEXT'
Tu es un expert en management et leadership avec une expertise en gestion d'équipe et développement organisationnel.
Aide sur le leadership, la prise de décision, la gestion du changement et le développement des compétences managériales.
Fournis des conseils pratiques sur la délégation, la motivation d'équipe et la résolution de conflits.
TEXT,
            'Entrepreneuriat' => <<<'TEXT'
Tu es un mentor en entrepreneuriat avec une expérience dans la création et le développement de startups.
Aide sur le développement de business plan, le pitch investisseurs, la validation de marché et la croissance startup.
Fournis des conseils pratiques sur le MVP, le product-market fit, le fundraising et le scaling.
TEXT,
            'Commerce' => <<<'TEXT'
Tu es un expert en commerce et retail avec une connaissance approfondie de la vente au détail et du e-commerce.
Aide sur les stratégies de merchandising, la gestion des stocks, l'expérience client et l'optimisation des ventes.
Fournis des conseils sur l'agencement de magasin, le pricing, les promotions et la gestion multi-canal.
TEXT,
        ];

        return $instructions[$subType] ?? "Tu es un expert professionnel spécialisé en {$subType}.\nFournis des conseils pratiques et actionnables basés sur les meilleures pratiques de ce domaine spécifique.\nUtilise un ton professionnel mais accessible.";
    }

    private function getEducationalInstructions(string $subType): string
    {
        $instructions = [
            'Mathématiques' => 'Tu es un professeur de mathématiques expérimenté. Explique les concepts avec des exemples clairs et progressifs.',
            'Sciences' => "Tu es un expert scientifique. Fournis des explications rigoureuses et des expériences quand c'est pertinent.",
            'Langues' => 'Tu es un professeur de langues. Aide avec la grammaire, le vocabulaire et la pratique orale.',
            'Histoire' => 'Tu es un historien passionné. Donne du contexte et des analyses critiques des événements.',
            'Géographie' => 'Tu es un expert en géographie. Explique les concepts physiques et humains avec précision.',
            'Informatique' => 'Tu es un expert en informatique. Aide avec la programmation, les algorithmes et les concepts techniques.',
            'Philosophie' => "Tu es un professeur de philosophie. Encourage la réflexion critique et l'analyse des concepts.",
            'Littérature' => 'Tu es un expert en littérature. Analyse les textes et aide à la rédaction.',
            'Physique' => 'Tu es un professeur de physique. Utilise des exemples concrets et des formules quand nécessaire.',
            'Chimie' => 'Tu es un professeur de chimie. Explique les réactions et les principes fondamentaux.',
            'Biologie' => 'Tu es un professeur de biologie. Explique les processus biologiques avec des exemples.',
        ];

        return $instructions[$subType] ?? "Tu es un tuteur expert en {$subType}.\nExplique les concepts de manière claire et progressive, en utilisant des exemples concrets.";
    }

    private function getSportInstructions(string $subType): string
    {
        $instructions = [
            'Perte de poids' => 'Tu es un coach spécialisé en perte de poids. Fournis des programmes adaptés et des conseils nutritionnels.',
            'Gain musculaire' => "Tu es un coach spécialisé en hypertrophie. Propose des plans d'entraînement progressifs.",
            'Endurance' => "Tu es un coach d'endurance. Propose des plans pour améliorer la cardio.",
            'Force' => 'Tu es un coach de force. Fournis des programmes axés sur la progression de charge.',
            'Flexibilité' => "Tu es un coach spécialisé en mobilité et flexibilité. Propose des routines d'étirement.",
            'Préparation compétition' => 'Tu es un coach spécialisé en préparation à la compétition. Aide à la planification et au suivi.',
            'Remise en forme' => 'Tu es un coach pour la remise en forme. Propose des routines simples et motivantes.',
            'Course à pied' => 'Tu es un coach de course à pied. Fournis des plans adaptés au niveau.',
            'Musculation' => 'Tu es un coach de musculation. Propose des splits et exercices adaptés.',
        ];

        return $instructions[$subType] ?? "Tu es un coach sportif expert en {$subType}.\nCrée des programmes personnalisés et motivants.";
    }

    private function getHealthInstructions(string $subType): string
    {
        $instructions = [
            'Nutrition' => 'Tu es un conseiller en nutrition. Propose des plans alimentaires équilibrés.',
            'Bien-être mental' => 'Tu es un conseiller en bien-être mental. Propose des exercices de relaxation et des conseils de gestion du stress.',
            'Sommeil' => "Tu es un expert du sommeil. Donne des conseils pour améliorer l'hygiène du sommeil.",
            'Gestion du stress' => 'Tu es un expert en gestion du stress. Propose des techniques concrètes et simples.',
            'Méditation' => 'Tu es un guide de méditation. Propose des routines adaptées au niveau.',
            'Yoga' => 'Tu es un professeur de yoga. Propose des séquences adaptées aux objectifs.',
            'Hydratation' => 'Tu es un conseiller en hydratation. Donne des conseils pratiques pour rester hydraté.',
            'Habitudes saines' => "Tu es un coach d'habitudes saines. Aide à mettre en place des routines durables.",
        ];

        return $instructions[$subType] ?? "Tu es un conseiller santé spécialisé en {$subType}.\nFournis des conseils pratiques et responsables.";
    }

    private function getCreativeInstructions(string $subType): string
    {
        $instructions = [
            'Écriture' => 'Tu es un coach en écriture. Aide à structurer les idées et améliorer le style.',
            'Brainstorming' => 'Tu es un facilitateur de brainstorming. Propose des techniques pour générer des idées.',
            'Design' => 'Tu es un designer expérimenté. Donne des conseils sur les principes de design.',
            'Photographie' => 'Tu es un photographe expert. Donne des conseils sur la composition et la lumière.',
            'Musique' => 'Tu es un coach musical. Aide à composer et arranger.',
            'Art visuel' => 'Tu es un mentor en art visuel. Propose des exercices créatifs.',
            'Vidéo' => 'Tu es un créateur vidéo. Donne des conseils sur le storyboard et le montage.',
            'Storytelling' => 'Tu es un expert en storytelling. Aide à créer des récits engageants.',
        ];

        return $instructions[$subType] ?? "Tu es un mentor créatif spécialisé en {$subType}.\nStimule la créativité avec des exercices et des exemples.";
    }

    private function getTravelInstructions(string $subType): string
    {
        $instructions = [
            'Découverte culturelle' => 'Tu es un expert des voyages culturels. Propose des itinéraires riches en découvertes.',
            'Aventure' => "Tu es un expert des voyages d'aventure. Propose des activités et préparatifs adaptés.",
            'Détente' => 'Tu es un expert des voyages détente. Propose des destinations relaxantes.',
            'Gastronomie' => 'Tu es un expert en voyages gastronomiques. Propose des expériences culinaires uniques.',
            'Budget backpacker' => 'Tu es un expert des voyages à petit budget. Propose des astuces et itinéraires économiques.',
            'Luxe' => 'Tu es un expert des voyages de luxe. Propose des expériences haut de gamme.',
            'Famille' => 'Tu es un expert des voyages en famille. Propose des activités adaptées.',
            'Solo' => "Tu es un expert des voyages en solo. Propose des conseils de sécurité et d'organisation.",
        ];

        return $instructions[$subType] ?? "Tu es un expert voyage spécialisé en {$subType}.\nFournis des conseils personnalisés et pratiques.";
    }

    private function getCuisineInstructions(string $subType): string
    {
        $instructions = [
            'Cuisine française' => 'Tu es un chef spécialisé en cuisine française. Fournis des recettes authentiques.',
            'Cuisine italienne' => 'Tu es un chef spécialisé en cuisine italienne. Propose des plats classiques et modernes.',
            'Cuisine asiatique' => 'Tu es un chef spécialisé en cuisine asiatique. Donne des recettes et techniques clés.',
            'Pâtisserie' => 'Tu es un pâtissier expert. Propose des recettes précises et des astuces.',
            'Végétarien' => 'Tu es un chef végétarien. Propose des recettes équilibrées et savoureuses.',
            'Végan' => 'Tu es un chef végan. Propose des alternatives végétales adaptées.',
            'Sans gluten' => 'Tu es un chef spécialisé sans gluten. Propose des recettes adaptées.',
            'Cuisine rapide' => 'Tu es un chef de cuisine rapide. Propose des recettes simples et efficaces.',
            'Gastronomie' => 'Tu es un chef gastronomique. Propose des recettes élaborées et raffinées.',
        ];

        return $instructions[$subType] ?? "Tu es un chef spécialisé en {$subType}.\nPartage des recettes détaillées et adaptées.";
    }

    private function getFinanceInstructions(string $subType): string
    {
        $instructions = [
            'Budgétisation' => 'Tu es un expert en budgétisation. Aide à créer et suivre un budget réaliste.',
            'Épargne' => 'Tu es un expert en épargne. Propose des stratégies simples et efficaces.',
            'Investissement' => 'Tu es un expert en investissement. Explique les principes et les risques.',
            'Réduction de dettes' => 'Tu es un expert en désendettement. Propose des plans concrets.',
            'Planification retraite' => 'Tu es un expert en retraite. Aide à planifier et anticiper.',
            'Immobilier' => 'Tu es un expert en immobilier. Donne des conseils généraux et prudents.',
            'Fiscalité' => 'Tu es un expert en fiscalité. Donne des informations générales et recommande un professionnel.',
        ];

        return $instructions[$subType] ?? "Tu es un conseiller financier spécialisé en {$subType}.\nFournis des conseils prudents et pratiques.";
    }

    private function getListAssistantTools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_list',
                    'description' => 'Crée une nouvelle liste ou sous-liste avec ses tâches et sous-listes optionnelles',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => [
                                'type' => 'string',
                                'description' => 'Titre de la liste',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Description optionnelle de la liste',
                            ],
                            'category' => [
                                'type' => 'string',
                                'enum' => ['tasks', 'projects', 'study', 'workout', 'wellness', 'creative', 'travel', 'meal', 'budget'],
                                'description' => 'Catégorie de la liste',
                            ],
                            'parentListId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste parente (pour créer une sous-liste)',
                            ],
                            'items' => [
                                'type' => 'array',
                                'description' => 'Liste des tâches à créer dans cette liste',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'content' => [
                                            'type' => 'string',
                                            'description' => 'Contenu de la tâche',
                                        ],
                                        'dueDate' => [
                                            'type' => 'string',
                                            'description' => "Date d'échéance de la tâche (format ISO 8601)",
                                        ],
                                        'metadata' => [
                                            'type' => 'object',
                                            'description' => 'Métadonnées optionnelles pour la tâche',
                                        ],
                                    ],
                                    'required' => ['content'],
                                ],
                            ],
                            'sublists' => [
                                'type' => 'array',
                                'description' => 'Liste des sous-listes à créer dans cette liste',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'title' => [
                                            'type' => 'string',
                                            'description' => 'Titre de la sous-liste',
                                        ],
                                        'description' => [
                                            'type' => 'string',
                                            'description' => 'Description de la sous-liste',
                                        ],
                                        'items' => [
                                            'type' => 'array',
                                            'description' => 'Tâches de cette sous-liste',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'content' => [
                                                        'type' => 'string',
                                                        'description' => 'Contenu de la tâche',
                                                    ],
                                                    'dueDate' => [
                                                        'type' => 'string',
                                                        'description' => "Date d'échéance",
                                                    ],
                                                ],
                                                'required' => ['content'],
                                            ],
                                        ],
                                    ],
                                    'required' => ['title'],
                                ],
                            ],
                        ],
                        'required' => ['title', 'category'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'update_list',
                    'description' => 'Met à jour une liste existante',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste à mettre à jour',
                            ],
                            'title' => [
                                'type' => 'string',
                                'description' => 'Nouveau titre',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Nouvelle description',
                            ],
                        ],
                        'required' => ['listId'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'delete_list',
                    'description' => "Supprime une liste. Si listId n'est pas fourni, utilise la liste actuellement ouverte.",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste à supprimer (optionnel si une liste est ouverte)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_list_details',
                    'description' => "Récupère les détails d'une liste spécifique. Si listId n'est pas fourni, utilise la liste actuellement ouverte.",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste (optionnel si une liste est ouverte)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_all_lists',
                    'description' => "Liste toutes les listes de l'utilisateur (racines uniquement)",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object)[],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_sublists',
                    'description' => "Récupère les sous-listes d'une liste. Si listId n'est pas fourni, utilise la liste actuellement ouverte.",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste parente (optionnel si une liste est ouverte)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'add_task',
                    'description' => "Ajoute une ou plusieurs tâches à une liste. Si listId n'est pas fourni, utilise la liste actuellement ouverte.",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste (optionnel si une liste est ouverte)',
                            ],
                            'tasks' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'title' => ['type' => 'string'],
                                        'description' => ['type' => 'string'],
                                        'dueDate' => ['type' => 'string'],
                                    ],
                                    'required' => ['title'],
                                ],
                                'description' => 'Tableau de tâches à ajouter',
                            ],
                        ],
                        'required' => ['tasks'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'update_task',
                    'description' => 'Met à jour une tâche existante',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste',
                            ],
                            'taskId' => [
                                'type' => 'string',
                                'description' => 'ID de la tâche',
                            ],
                            'title' => [
                                'type' => 'string',
                                'description' => 'Nouveau titre',
                            ],
                            'description' => [
                                'type' => 'string',
                                'description' => 'Nouvelle description',
                            ],
                            'completed' => [
                                'type' => 'boolean',
                                'description' => 'État de complétion',
                            ],
                        ],
                        'required' => ['listId', 'taskId'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'delete_task',
                    'description' => 'Supprime une tâche',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste',
                            ],
                            'taskId' => [
                                'type' => 'string',
                                'description' => 'ID de la tâche',
                            ],
                        ],
                        'required' => ['listId', 'taskId'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'complete_task',
                    'description' => 'Marque une tâche comme complétée ou non complétée',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste',
                            ],
                            'taskId' => [
                                'type' => 'string',
                                'description' => 'ID de la tâche',
                            ],
                            'completed' => [
                                'type' => 'boolean',
                                'description' => 'État de complétion',
                            ],
                        ],
                        'required' => ['listId', 'taskId', 'completed'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_list_tasks',
                    'description' => "Récupère toutes les tâches d'une liste",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste',
                            ],
                        ],
                        'required' => ['listId'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'set_due_date',
                    'description' => 'Définit une échéance pour une liste ou une tâche',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste',
                            ],
                            'taskId' => [
                                'type' => 'string',
                                'description' => 'ID de la tâche (optionnel, si null applique à la liste)',
                            ],
                            'dueDate' => [
                                'type' => 'string',
                                'description' => "Date d'échéance au format ISO",
                            ],
                        ],
                        'required' => ['listId', 'dueDate'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'set_recurrence',
                    'description' => 'Définit une récurrence pour une liste',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste',
                            ],
                            'frequency' => [
                                'type' => 'string',
                                'enum' => ['daily', 'weekly', 'monthly'],
                                'description' => 'Fréquence de récurrence',
                            ],
                            'interval' => [
                                'type' => 'number',
                                'description' => 'Intervalle (ex: tous les 2 jours)',
                            ],
                        ],
                        'required' => ['listId', 'frequency', 'interval'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_tasks',
                    'description' => 'Recherche des tâches par mot-clé',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Terme de recherche',
                            ],
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste (optionnel, si null recherche dans toutes)',
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'find_overdue_tasks',
                    'description' => 'Trouve toutes les tâches en retard',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste (optionnel)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_statistics',
                    'description' => 'Obtient des statistiques sur les listes et tâches',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listId' => [
                                'type' => 'string',
                                'description' => 'ID de la liste (optionnel, si null pour toutes)',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_tasks_by_date',
                    'description' => 'Récupère les tâches pour une date spécifiée (aujourd\'hui, demain, ou date spécifique)',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'dateType' => [
                                'type' => 'string',
                                'enum' => ['today', 'tomorrow', 'specific'],
                                'description' => 'Type de date: "today" (aujourd\'hui), "tomorrow" (demain), ou "specific" (date spécifique)',
                            ],
                            'date' => [
                                'type' => 'string',
                                'description' => 'Date au format ISO (YYYY-MM-DD) - requis pour dateType="specific"',
                            ],
                        ],
                        'required' => ['dateType'],
                    ],
                ],
            ],
        ];
    }
}
