<?php namespace App;

use App\Models\Roles;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use League\Flysystem\Exception;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Illuminate\Support\Facades\DB;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    const ADMIN = 2;
    const INSPECTOR = 3;
    const CLIENT = 4;
  const OFFICE = 5;

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
            $adjuster->title = $adjuster->first_name . ' ' . $adjuster->last_name .
                ' (' . $adjuster->insurance_company . ')';
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
//      if ($inspectors != null) {
//        foreach ($inspectors as $inspector) {
//          $inspector->profile;
//        }
//      }

      return response()->json(compact('inspectors'));
    }

    /**
     * @param $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function findUsersByType($type)
    {
        try {
          $users = DB::table('user')
            ->select('user.id', 'user.email', 'ru.role_id', 'up.*')
            ->leftJoin('user_profiles as up', 'user.id', '=', 'up.user_id')
            ->leftJoin('roles_user as ru', 'user.id', '=', 'ru.user_id')
            ->orderBy('up.first_name')
            ->orderBy('up.last_name')
            ->where('ru.role_id', $type)
            ->get();

          return $users;
        } catch (Exception $e) {
            return response()->json(compact('e'));
        } catch (FatalErrorException $e) {
            return response()->json(compact('e'));
        }
    }
}
