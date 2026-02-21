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

    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'user_id', '_id');
    }

    public function lists()
    {
        return $this->hasMany(ListModel::class, 'user_id', '_id');
    }
}
