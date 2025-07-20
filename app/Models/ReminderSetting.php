<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReminderSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'days_before_reminder',
        'reminder_frequency_days',
        'max_reminders',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
