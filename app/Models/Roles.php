<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/10/16
 * Time: 10:55 AM
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    /**
     * @var string Roles table
     */
    protected $table = 'roles';

    /**
     * @var array fields to be returned in JSON
     */
    protected $fillable = ['name', 'description'];

    /**
     * @var array fields to be hidden
     */
    protected $hidden = ['id'];

    public function users() {
        return $this->belongsToMany('App\User', 'roles_user', 'role_id');
    }
}