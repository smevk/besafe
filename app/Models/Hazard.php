<?php

namespace App\Models;

use App\Traits\CommonRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hazard extends Model
{
    use HasFactory;
    use CommonRelation;

    public static function getRouteName()
    {
        return 'hazards.show'; //show route name
    }


    public function risk_level()
    {
        return $this->belongsTo(MetaRiskLevel::class, 'meta_risk_level_id');
    }

    public function department_tag()
    {
        return $this->belongsTo(MetaDepartmentTag::class, 'meta_department_tag_id');
    }


    public function initial_attachements()
    {
        return $this->common_Attachements()->where('form_input_name', 'initial_attachements');
    }
    public function attachements()
    {
        return $this->common_Attachements()->where('form_input_name', 'attachements');
    }


}