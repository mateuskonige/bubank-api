<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory, HasUuids, LogsActivity;

    protected $fillable = [
        'account_id',
        'destination_account_id',
        'type',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'account_id',
                'destination_account_id',
                'type',
                'amount',
                'status',
            ]);
    }
}
