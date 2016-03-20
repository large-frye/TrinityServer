<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/14/16
 * Time: 3:17 PM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class WorkorderStatuses extends Model
{
    protected $table = 'work_order_statuses';

    protected $fillable = ['name'];

    public function workorder() {
        return $this->belongsToMany('\App\Models\Workorder');
    }
}