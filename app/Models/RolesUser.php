<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/13/16
 * Time: 1:52 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class RolesUser extends Model
{
    protected $table = 'roles_user';
    protected $fillable = ['role_id', 'user_id'];

    public function users() {
        return $this->belongsTo('\App\User');
    }
}