<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Eloquent\Model;

class Profile extends Model
{
    use Notifiable;

    protected $connection = 'mongodb';

    protected $collection = 'users';

    protected $fillable = [
        'email',
        'full_name',
        'locale',
        'notification_settings',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'notification_settings' => 'array',
    ];

    /**
     * Remove invalid push tokens from this profile's notification settings.
     *
     * @param  array<string>  $invalidTokens
     */
    public function removeExpoPushTokens(array $invalidTokens): void
    {
        if (empty($invalidTokens)) {
            return;
        }

        $settings = (array) ($this->notification_settings ?? []);
        $tokens = array_values(array_filter((array) ($settings['expo_push_tokens'] ?? [])));

        $settings['expo_push_tokens'] = array_values(
            array_filter($tokens, fn ($token) => ! in_array($token, $invalidTokens, true))
        );

        $this->notification_settings = $settings;
        $this->save();
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'user_id', '_id');
    }

    public function lists()
    {
        return $this->hasMany(ListModel::class, 'user_id', '_id');
    }
}
