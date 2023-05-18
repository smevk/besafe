<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalExternalAuditAnswerAttachement extends Model
{
    use HasFactory;
    protected $table = "ie_audit_answer_attachements";

    public function attachements()
    {
        return $this->hasMany(InternalExternalAuditAnswerAttachement::class);
    }
}