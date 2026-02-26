<?php

namespace App\Constants;

class FitnessChallengeTemplate
{
    public const TEMPLATES = [
            [
                'type' => 'streak',
                'title' => '3 séances sans rater',
                'description' => 'Complétez 3 séances consécutives sans en manquer une',
                'target' => 3,
                'icon' => 'flame',
                'difficulty' => 'easy',
            ],
            [
                'type' => 'streak',
                'title' => '7 jours de régularité',
                'description' => 'Entraînez-vous 7 jours consécutifs',
                'target' => 7,
                'icon' => 'flame',
                'difficulty' => 'medium',
            ],
            [
                'type' => 'streak',
                'title' => '14 jours de constance',
                'description' => '2 semaines d\'entraînement sans interruption',
                'target' => 14,
                'icon' => 'flame',
                'difficulty' => 'hard',
            ],
            [
                'type' => 'thirty_days',
                'title' => 'Challenge 30 jours',
                'description' => '30 jours d\'entraînement consécutifs',
                'target' => 30,
                'icon' => 'calendar',
                'difficulty' => 'extreme',
            ],
            [
                'type' => 'volume_double',
                'title' => 'Doubler le volume',
                'description' => 'Doublez votre volume d\'entraînement par rapport à la semaine dernière',
                'target' => 2,
                'icon' => 'trending-up',
                'difficulty' => 'hard',
            ],
            [
                'type' => 'perfect_week',
                'title' => 'Semaine parfaite',
                'description' => 'Complétez tous les exercices planifiés cette semaine',
                'target' => 1,
                'icon' => 'star',
                'difficulty' => 'medium',
            ],
            [
                'type' => 'weight_increase',
                'title' => 'Toujours plus lourd',
                'description' => 'Augmentez vos charges 4 semaines consécutives',
                'target' => 4,
                'icon' => 'arrow-up',
                'difficulty' => 'hard',
            ],
            [
                'type' => 'early_bird',
                'title' => 'Lève-tôt',
                'description' => 'Complétez 5 séances avant 8h du matin',
                'target' => 5,
                'icon' => 'sunrise',
                'difficulty' => 'medium',
            ],
        ];
}
