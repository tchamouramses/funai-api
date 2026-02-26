<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FitnessAIService
{
    private string $apiKey;

    private string $baseUrl = 'https://api.openai.com/v1';

    private string $model = 'gpt-4o';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');

        if (! $this->apiKey) {
            throw new \Exception('OpenAI API key not configured');
        }
    }

    /**
     * Generate a structured workout program based on user profile.
     */
    public function generateProgram(array $profile, string $locale = 'fr'): array
    {
        $goal = $profile['goal'] ?? 'general_fitness';
        $customGoal = $profile['customGoal'] ?? null;
        $height = $profile['height'] ?? 170;
        $weight = $profile['weight'] ?? 70;
        $sex = $profile['sex'] ?? 'not_specified';
        $age = $profile['age'] ?? 25;
        $level = $profile['level'] ?? 'beginner';

        $goalLabel = $this->getGoalLabel($goal, $customGoal, $locale);
        $levelLabel = $this->getLevelLabel($level, $locale);
        $sexLabel = $this->getSexLabel($sex, $locale);

        $prompt = $this->buildPrompt($goalLabel, $height, $weight, $sexLabel, $age, $levelLabel, $locale);

        $response = $this->callChatCompletion($prompt);

        return $this->parseResponse($response);
    }

    /**
     * Generate a weekly summary for a fitness list.
     */
    public function generateWeeklySummary(array $stats, array $profile, string $locale = 'fr'): string
    {
        $lang = $locale === 'fr' ? 'français' : 'English';

        $prompt = "Tu es un coach sportif bienveillant et motivant. Génère un résumé hebdomadaire personnalisé en {$lang} basé sur ces données:\n\n";
        $prompt .= "Profil: {$profile['sex']}, {$profile['age']} ans, {$profile['height']}cm, {$profile['weight']}kg, niveau {$profile['level']}\n";
        $prompt .= "Objectif: {$profile['goal']}\n\n";
        $prompt .= "Statistiques de la semaine:\n";
        $prompt .= "- Séances complétées: {$stats['completed_sessions']}/{$stats['planned_sessions']}\n";
        $prompt .= "- Exercices complétés: {$stats['completed_exercises']}/{$stats['total_exercises']}\n";
        $prompt .= "- Volume total: {$stats['total_volume']} kg\n";
        $prompt .= "- Série en cours: {$stats['current_streak']} jours\n";

        if (! empty($stats['improvements'])) {
            $prompt .= "- Améliorations: ".implode(', ', $stats['improvements'])."\n";
        }

        $prompt .= "\nGénère un résumé court (3-4 phrases) qui:\n";
        $prompt .= "1. Félicite les progrès réalisés\n";
        $prompt .= "2. Identifie un point d'amélioration\n";
        $prompt .= "3. Donne une motivation pour la semaine suivante\n";
        $prompt .= "\nRéponds uniquement avec le texte du résumé, sans formatage markdown.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("{$this->baseUrl}/chat/completions", [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'Tu es un coach sportif professionnel.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 300,
            'temperature' => 0.7,
        ]);

        if ($response->failed()) {
            Log::error('OpenAI weekly summary failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Failed to generate weekly summary');
        }

        return $response->json('choices.0.message.content', 'Bonne semaine d\'entraînement !');
    }

    private function buildPrompt(string $goal, int $height, int $weight, string $sex, int $age, string $level, string $locale): string
    {
        $lang = $locale === 'fr' ? 'français' : 'English';

        return <<<PROMPT
Tu es un coach sportif professionnel et certifié. Crée un programme d'entraînement personnalisé en {$lang}.

**Profil de l'utilisateur:**
- Objectif: {$goal}
- Taille: {$height} cm
- Poids: {$weight} kg
- Sexe: {$sex}
- Âge: {$age} ans
- Niveau: {$level}

**Instructions:**
1. Crée un programme adapté au niveau et à l'objectif
2. Organise les exercices par jours/séances (sous-listes)
3. Pour chaque exercice, indique: séries, répétitions, poids suggéré (en kg), durée (en minutes si applicable)
4. Propose un planning hebdomadaire avec les jours de repos
5. Le programme doit être réaliste et progressif

**Format de réponse JSON strictement comme suit:**
{
  "programName": "Nom du programme",
  "description": "Description courte du programme",
  "durationWeeks": 4,
  "sessionsPerWeek": 3,
  "schedule": {
    "daysOfWeek": [1, 3, 5],
    "restDays": [0, 2, 4, 6]
  },
  "subLists": [
    {
      "title": "Jour 1 - Titre de la séance",
      "description": "Description de la séance",
      "exercises": [
        {
          "content": "Nom de l'exercice",
          "sets": 3,
          "reps": 10,
          "weight": 20,
          "duration": null,
          "notes": "Conseil technique court"
        }
      ]
    }
  ],
  "tips": ["Conseil 1", "Conseil 2", "Conseil 3"]
}

Réponds UNIQUEMENT avec le JSON valide, sans texte avant ou après.
PROMPT;
    }

    private function callChatCompletion(string $prompt): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post("{$this->baseUrl}/chat/completions", [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un coach sportif professionnel. Tu réponds uniquement en JSON valide.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'response_format' => ['type' => 'json_object'],
            'max_tokens' => 4000,
            'temperature' => 0.7,
        ]);

        if ($response->failed()) {
            Log::error('OpenAI fitness program generation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Failed to generate fitness program from AI');
        }

        $content = $response->json('choices.0.message.content');

        if (! $content) {
            throw new \Exception('Empty response from AI');
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid JSON from AI', ['content' => $content]);
            throw new \Exception('Invalid JSON response from AI');
        }

        return $decoded;
    }

    private function parseResponse(array $data): array
    {
        // Validate required fields
        if (! isset($data['subLists']) || ! is_array($data['subLists'])) {
            throw new \Exception('AI response missing subLists');
        }

        return [
            'programName' => $data['programName'] ?? 'Programme personnalisé',
            'description' => $data['description'] ?? '',
            'durationWeeks' => $data['durationWeeks'] ?? 4,
            'sessionsPerWeek' => $data['sessionsPerWeek'] ?? 3,
            'schedule' => $data['schedule'] ?? ['daysOfWeek' => [1, 3, 5], 'restDays' => [0, 2, 4, 6]],
            'subLists' => array_map(function ($subList) {
                return [
                    'title' => $subList['title'] ?? 'Séance',
                    'description' => $subList['description'] ?? '',
                    'exercises' => array_map(function ($exercise) {
                        return [
                            'content' => $exercise['content'] ?? 'Exercice',
                            'sets' => $exercise['sets'] ?? null,
                            'reps' => $exercise['reps'] ?? null,
                            'weight' => $exercise['weight'] ?? null,
                            'duration' => $exercise['duration'] ?? null,
                            'notes' => $exercise['notes'] ?? null,
                        ];
                    }, $subList['exercises'] ?? []),
                ];
            }, $data['subLists']),
            'tips' => $data['tips'] ?? [],
        ];
    }

    private function getGoalLabel(string $goal, ?string $customGoal, string $locale): string
    {
        if ($goal === 'custom' && $customGoal) {
            return $customGoal;
        }

        $labels = [
            'weight_loss' => $locale === 'fr' ? 'Perte de poids' : 'Weight loss',
            'muscle_gain' => $locale === 'fr' ? 'Prise de masse musculaire' : 'Muscle gain',
            'endurance' => $locale === 'fr' ? 'Amélioration de l\'endurance' : 'Endurance improvement',
            'general_fitness' => $locale === 'fr' ? 'Remise en forme générale' : 'General fitness',
            'sport_prep' => $locale === 'fr' ? 'Préparation sportive' : 'Sport preparation',
            'flexibility' => $locale === 'fr' ? 'Flexibilité et mobilité' : 'Flexibility & mobility',
        ];

        return $labels[$goal] ?? ($locale === 'fr' ? 'Remise en forme générale' : 'General fitness');
    }

    private function getLevelLabel(string $level, string $locale): string
    {
        $labels = [
            'beginner' => $locale === 'fr' ? 'Débutant' : 'Beginner',
            'intermediate' => $locale === 'fr' ? 'Intermédiaire' : 'Intermediate',
            'advanced' => $locale === 'fr' ? 'Avancé' : 'Advanced',
        ];

        return $labels[$level] ?? ($locale === 'fr' ? 'Débutant' : 'Beginner');
    }

    private function getSexLabel(string $sex, string $locale): string
    {
        $labels = [
            'male' => $locale === 'fr' ? 'Homme' : 'Male',
            'female' => $locale === 'fr' ? 'Femme' : 'Female',
            'not_specified' => $locale === 'fr' ? 'Non spécifié' : 'Not specified',
        ];

        return $labels[$sex] ?? ($locale === 'fr' ? 'Non spécifié' : 'Not specified');
    }
}
