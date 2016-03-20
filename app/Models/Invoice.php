<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/15/16
 * Time: 3:44 PM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Invoice extends Model {

    protected $table = 'invoice';
    protected $fillable = ['date'];
    public function workorder() {
        return $this->belongsTo('\App\Models\Workorder');
    }
}