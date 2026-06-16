<?php

namespace HMsoft\Tools\Features\Attribute\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeTranslation extends Model
{
    protected $table = 'attribute_translations';
    protected $guarded = ['id'];
    public $timestamps = false;
}
