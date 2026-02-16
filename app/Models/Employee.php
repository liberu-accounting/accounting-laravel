

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'position',
        'hire_date',
        'tax_id',
        'national_insurance_number',
        'starter_declaration',
        'p45_issue_date',
        'has_student_loan',
        'student_loan_plan',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'p45_issue_date' => 'date',
        'has_student_loan' => 'boolean',
    ];

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
}