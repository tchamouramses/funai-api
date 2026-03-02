<?php

namespace App\Constants;

class FinanceDefaults
{
    /**
     * Default income categories.
     */
    public const INCOME_CATEGORIES = [
        [
            'name' => 'Salaire',
            'icon' => 'briefcase',
            'color' => '#22C55E',
        ],
        [
            'name' => 'Freelance',
            'icon' => 'laptop',
            'color' => '#3B82F6',
        ],
        [
            'name' => 'Investissement',
            'icon' => 'trending-up',
            'color' => '#8B5CF6',
        ],
        [
            'name' => 'Commission',
            'icon' => 'percent',
            'color' => '#F59E0B',
        ],
        [
            'name' => 'Vente',
            'icon' => 'shopping-bag',
            'color' => '#EC4899',
        ],
        [
            'name' => 'Abonnement',
            'icon' => 'repeat',
            'color' => '#06B6D4',
        ],
        [
            'name' => 'Don',
            'icon' => 'heart',
            'color' => '#EF4444',
        ],
        [
            'name' => 'Autre revenu',
            'icon' => 'plus-circle',
            'color' => '#6B7280',
        ],
    ];

    /**
     * Default expense categories.
     */
    public const EXPENSE_CATEGORIES = [
        [
            'name' => 'Alimentation',
            'icon' => 'utensils',
            'color' => '#22C55E',
        ],
        [
            'name' => 'Transport',
            'icon' => 'car',
            'color' => '#3B82F6',
        ],
        [
            'name' => 'Logement',
            'icon' => 'home',
            'color' => '#F59E0B',
        ],
        [
            'name' => 'Santé',
            'icon' => 'heart-pulse',
            'color' => '#EF4444',
        ],
        [
            'name' => 'Éducation',
            'icon' => 'graduation-cap',
            'color' => '#8B5CF6',
        ],
        [
            'name' => 'Loisirs',
            'icon' => 'gamepad-2',
            'color' => '#EC4899',
        ],
        [
            'name' => 'Shopping',
            'icon' => 'shopping-cart',
            'color' => '#F97316',
        ],
        [
            'name' => 'Factures',
            'icon' => 'file-text',
            'color' => '#64748B',
        ],
        [
            'name' => 'Abonnements',
            'icon' => 'repeat',
            'color' => '#06B6D4',
        ],
        [
            'name' => 'Épargne',
            'icon' => 'piggy-bank',
            'color' => '#D4AF37',
        ],
        [
            'name' => 'Autre dépense',
            'icon' => 'plus-circle',
            'color' => '#6B7280',
        ],
    ];

    /**
     * Supported currencies.
     */
    public const CURRENCIES = [
        'XAF' => ['name' => 'Franc CFA (CEMAC)', 'symbol' => 'FCFA'],
        'XOF' => ['name' => 'Franc CFA (UEMOA)', 'symbol' => 'FCFA'],
        'EUR' => ['name' => 'Euro', 'symbol' => '€'],
        'USD' => ['name' => 'Dollar américain', 'symbol' => '$'],
        'GBP' => ['name' => 'Livre sterling', 'symbol' => '£'],
        'NGN' => ['name' => 'Naira nigérian', 'symbol' => '₦'],
        'GHS' => ['name' => 'Cedi ghanéen', 'symbol' => 'GH₵'],
        'MAD' => ['name' => 'Dirham marocain', 'symbol' => 'MAD'],
        'ZAR' => ['name' => 'Rand sud-africain', 'symbol' => 'R'],
        'KES' => ['name' => 'Shilling kényan', 'symbol' => 'KSh'],
        'CAD' => ['name' => 'Dollar canadien', 'symbol' => 'CA$'],
        'CHF' => ['name' => 'Franc suisse', 'symbol' => 'CHF'],
    ];

    /**
     * Transaction statuses.
     */
    public const TRANSACTION_STATUSES = [
        'planned',
        'received',
        'paid',
        'cancelled',
    ];

    /**
     * Payment methods.
     */
    public const PAYMENT_METHODS = [
        'cash',
        'card',
        'bank_transfer',
        'mobile_money',
        'check',
        'other',
    ];

    /**
     * Budget periods.
     */
    public const BUDGET_PERIODS = [
        'weekly',
        'monthly',
        'quarterly',
        'yearly',
        'custom',
    ];

    /**
     * Default alert thresholds (percentages).
     */
    public const DEFAULT_ALERT_THRESHOLDS = [70, 90, 100];

    /**
     * Default currency.
     */
    public const DEFAULT_CURRENCY = 'XAF';
}
