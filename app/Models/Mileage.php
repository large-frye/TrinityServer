<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 5/25/16
 * Time: 7:14 PM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Mileage extends Model
{
    protected $table = 'mileage';
    protected $fillable = ['week'];
}