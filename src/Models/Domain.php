<?php

namespace Spatie\Multitenancy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Domain extends Model
{
    use UsesLandlordConnection;
    use HasFactory;
}
