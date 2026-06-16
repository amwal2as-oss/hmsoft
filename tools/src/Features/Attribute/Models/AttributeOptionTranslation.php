<?php

namespace HMsoft\Tools\Features\Attribute\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeOptionTranslation extends Model
{
    protected $table = 'attribute_option_translations';
    protected $guarded = ['id'];
    public $timestamps = false;
}
