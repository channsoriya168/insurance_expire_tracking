<?php

namespace App\Models;

use Database\Factories\PolicyTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class PolicyType extends Model
{
    /** @use HasFactory<PolicyTypeFactory> */
    use HasFactory;
}
