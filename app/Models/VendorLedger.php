<?php
// app/Models/VendorLedger.php

namespace App\Models;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;

class VendorLedger extends Model
{
    protected $guarded = [];

    // app/Models/VendorLedger.php

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
