<?php

namespace App\Models;

use Database\Factories\InsuranceCompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class InsuranceCompany extends Model
{
    /** @use HasFactory<InsuranceCompanyFactory> */
    use HasFactory;
}
