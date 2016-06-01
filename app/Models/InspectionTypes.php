<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 5/31/16
 * Time: 10:27 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionTypes extends Model
{
    public $table = 'inspection_types';
    public $fillable = ['id', 'name'];
}