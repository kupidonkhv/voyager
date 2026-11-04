<?php

namespace TCG\Voyager\Policies;

use TCG\Voyager\Contracts\User;
use TCG\Voyager\Facades\Voyager;

class MenuItemPolicy extends BasePolicy
{
    protected static $datatypes = null;
    protected static $permissions = null;

    /**
     * Check if user has an associated permission.
     *
     * @param User   $user
     * @param object $model
     * @param string $action
     *
     * @return bool
     */
    protected function checkPermission(User $user, $model, $action)
    {
        if (self::$permissions == null) {
            self::$permissions = Voyager::model('Permission')->all();
        }

        if (self::$datatypes == null) {
            self::$datatypes = Voyager::model('DataType')::all()->keyBy('slug');
        }

        $regex = str_replace('/', '\/', preg_quote(route('voyager.dashboard')));
        $slug = preg_replace('/'.$regex.'/', '', $model->link(true));
        $slug = str_replace('/', '', $slug);

        if ($str = self::$datatypes->get($slug)) {
            $slug = $str->name;
        }

        if ($slug == '') {
            $slug = 'admin';
        } elseif ($slug == 'compass' && !\App::environment('local') && !config('voyager.compass_in_production', false)) {
            return false;
        }

        if (empty($action)) {
            $action = 'browse';
        }

        // If permission doesn't exist, we can't check it!
        if (!self::$permissions->contains('key', $action.'_'.$slug)) {
            // [fix-v2] показ пункта меню не должен зависеть от существования права раздела
            // (например, route voyager.activity.index -> browse_activity, а ключ в БД browse_activity_logs):
            // fail-open только для отображающих способностей, сама страница раздела авторизуется отдельно.
            if (in_array($action, ['browse', 'view'], true)) {
                return true;
            }

            // [fix-v2] fail-closed для изменений, но правка пункта меню — операция над разделом «Меню»:
            // если права <action>_<slug> не существует, откатываемся на <action>_menus.
            return $user->hasPermission($action.'_menus');
        }

        return $user->hasPermission($action.'_'.$slug);
    }
}
