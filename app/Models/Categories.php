<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 5/30/16
 * Time: 10:57 AM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    public $table = 'categories';
    public $fillable = ['id', 'parent_id', 'name', 'slug'];
}