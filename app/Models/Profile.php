<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/2/16
 * Time: 9:32 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $table = 'user_profiles';
    protected $fillable = ['first_name', 'last_name', 'phone', 'geographic_region', 'insurance_company', 'color'];

    public function user() {
        return $this->belongsTo('\App\User');
    }
}