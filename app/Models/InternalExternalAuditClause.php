<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalExternalAuditClause extends Model
{
    use HasFactory;

    public function audit_hall()
    {
        return $this->belongsTo(MetaAuditHall::class);
    }

    public function audit_type()
    {
        return $this->belongsTo(MetaAuditType::class);
    }


}