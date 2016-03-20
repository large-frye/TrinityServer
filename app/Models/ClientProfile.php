<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/11/16
 * Time: 9:37 PM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class ClientProfile extends Model {
    protected $table = 'client_profile';
    protected $fillable = ['name', 'phone_1', 'phone_2', 'insurance_company'];

    public function User() {
        return $this->belongsTo('\App\User');
    }

    public function Workorder() {
        return $this->belongsToMany('App\Models\Workorders');
    }
}