<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['collection', 'path', 'name', 'mime', 'size'])]
class IncidentAttachment extends Model {}
