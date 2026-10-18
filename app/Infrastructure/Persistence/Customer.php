<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'document', 'document_type', 'email', 'phone'];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
