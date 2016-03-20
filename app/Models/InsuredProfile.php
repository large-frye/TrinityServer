<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/11/16
 * Time: 8:47 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuredProfile extends Model {
    protected $table = 'insured_profile';
    protected $fillable = ['claim_num', 'first_name', 'last_name', 'address', 'city', 'state', 'zip_code', 'phone_1', 'phone_2', 'phone_3'];

    public function User() {
        return $this->belongsTo('\App\User');
    }

    public function Workorder() {
        return $this->belongsToMany('App\Models\Workorders');
    }
}