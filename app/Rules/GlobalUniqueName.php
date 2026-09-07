<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class GlobalUniqueName implements ValidationRule
{
    protected ?string $ignoreTable;
    protected mixed $ignoreId;
    protected string $ignoreColumn;

    /**
     * Create a new rule instance.
     *
     * @param string|null $ignoreTable Table to exclude current record from collision check during edit
     * @param mixed $ignoreId ID of record to ignore in $ignoreTable
     * @param string $ignoreColumn Column name of ID in $ignoreTable (default: 'id')
     */
    public function __construct(?string $ignoreTable = null, mixed $ignoreId = null, string $ignoreColumn = 'id')
    {
        $this->ignoreTable = $ignoreTable;
        $this->ignoreId = $ignoreId;
        $this->ignoreColumn = $ignoreColumn;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = trim((string) $value);

        if ($name === '') {
            return;
        }

        // List of entity tables and their respective name/title columns & entity label
        $entities = [
            ['table' => 'products',      'column' => 'name',          'label' => 'Product'],
            ['table' => 'account_heads', 'column' => 'name',          'label' => 'Account Head'],
            ['table' => 'accounts',      'column' => 'title',         'label' => 'Sub-Account'],
            ['table' => 'customers',     'column' => 'customer_name', 'label' => 'Customer'],
            ['table' => 'vendors',       'column' => 'name',          'label' => 'Vendor'],
            ['table' => 'categories',    'column' => 'name',          'label' => 'Category'],
            ['table' => 'subcategories', 'column' => 'name',          'label' => 'Subcategory'],
            ['table' => 'brands',        'column' => 'name',          'label' => 'Brand'],
        ];

        foreach ($entities as $entity) {
            $query = DB::table($entity['table'])->where(DB::raw("TRIM({$entity['column']})"), '=', $name);

            if ($this->ignoreTable && $entity['table'] === $this->ignoreTable && $this->ignoreId !== null && $this->ignoreId !== '') {
                $query->where($this->ignoreColumn, '!=', $this->ignoreId);
            }

            if ($query->exists()) {
                $fail("The name '{$name}' is already taken by a {$entity['label']}. System-wide name must be unique.");
                return;
            }
        }
    }
}
