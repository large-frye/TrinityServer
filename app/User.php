<?php namespace App;

use App\Models\Roles;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use League\Flysystem\Exception;
use Symfony\Component\Debug\Exception\FatalErrorException;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    const ADMIN = 2;
    const INSPECTOR = 3;
    const CLIENT = 4;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'name', 'email', 'password'];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    public function profile()
    {
        return $this->hasOne('App\Models\Profile', 'user_id');
    }

    public function roles()
    {
        return $this->hasMany('App\Models\Roles', 'user_id', 'role_id');
    }

    public function rolesUser()
    {
        return $this->hasMany('App\Models\RolesUser');
    }

    public function workorders()
    {
        return $this->hasMany('App\Models\Workorder', 'adjuster_id');
    }

    /**
     * @param $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function findAdjusters($type)
    {
        $adjusters = $this->findUsersByType($type);
        foreach ($adjusters as $adjuster) {
            $adjuster->profile;
            $adjuster->title = $adjuster->profile->first_name . ' ' . $adjuster->profile->last_name .
                ' (' . $adjuster->profile->insurance_company . ')';
        }

        return response()->json(compact('adjusters'));
    }

    /**
     * @param $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function findInspectors($type)
    {
        $inspectors = $this->findUsersByType($type);
        return response()->json(compact('inspectors'));
    }

    /**
     * @param $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function findUsersByType($type)
    {
        try {
            $users = Roles::find($type)->users()->get();
            foreach($users as $user) {
                $user->profile;
            }
            return $users;
        } catch (Exception $e) {
            return response()->json(compact('e'));
        } catch (FatalErrorException $e) {
            return response()->json(compact('e'));
        }
    }
}
