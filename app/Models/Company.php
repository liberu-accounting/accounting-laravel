<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class Company extends Model
{
    use HasFactory;
    
    protected $primaryKey = "company_id";

    protected $fillable = [
        "company_name",
        "company_address",
        "company_email",
        "company_phone",
        "company_city",
        "company_tin",
        "company_logo"
    ];
}
