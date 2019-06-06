<?php

namespace Flextype;

use Flextype\Component\Filesystem\Filesystem;
use Flextype\Component\Session\Session;
use Flextype\Component\Registry\Registry;
use Flextype\Component\Text\Text;
use function Flextype\Component\I18n\__;
use Psr\Container\ContainerInterface;

class UsersController extends Controller
{
    public function profile($request, $response)
    {
        return $this->container->get('view')->render(
            $response,
            'plugins/admin/views/templates/users/profile.html'
      );
    }

    public function login($request, $response)
    {
        if (!Users::isLoggedIn()) {
            return $this->container->get('view')->render(
                $response,
                'plugins/admin/views/templates/users/login.html',
                [
                                  'user_is_logged' => Users::isLoggedIn()
                                 ]
          );
        } else {
            return $response->withRedirect($this->container->get('router')->pathFor('admin.users.registration'));
        }
    }

    public function loginProcess($request, $response)
    {
        $data = $request->getParsedBody();

        if (Filesystem::has($_user_file = PATH['site'] . '/accounts/' . $data['username'] . '.json')) {
            $user_file = JsonParser::decode(Filesystem::read($_user_file));
            if (password_verify(trim($data['password']), $user_file['hashed_password'])) {
                Session::set('username', $user_file['username']);
                Session::set('role', $user_file['role']);
                return $response->withRedirect($this->container->get('router')->pathFor('admin.entries.index'));
            } else {
                //Notification::set('error', __('admin_message_wrong_username_password'));
            }
        } else {
            //Notification::set('error', __('admin_message_wrong_username_password'));
        }
    }

    public function registration($request, $response)
    {
        if (!Users::isLoggedIn()) {
            return $this->view->render(
                $response,
                'plugins/admin/views/templates/users/registration.html'
            );
        } else {
            return $response->withRedirect($this->container->get('router')->pathFor('admin.entires.index'));
        }
    }

    /**
     * registrationProcess
     */
    public function registrationProcess($request, $response)
    {
        // Get POST data
        $data = $request->getParsedBody();

        if (!Filesystem::has($_user_file = PATH['site'] . '/accounts/' . Text::safeString($data['username']) . '.json')) {
            if (Filesystem::write(
                PATH['site'] . '/accounts/' . $data['username'] . '.json',
                JsonParser::encode(['username' => Text::safeString($data['username']),
                                         'hashed_password' => password_hash($data['password'], PASSWORD_BCRYPT),
                                         'email' => $data['email'],
                                         'role'  => 'admin',
                                         'state' => 'enabled'])
            )) {
                return $response->withRedirect($this->container->get('router')->pathFor('admin.users.login'));
            } else {
                return $response->withRedirect($this->container->get('router')->pathFor('admin.users.registration'));
            }
        } else {
            return $response->withRedirect($this->container->get('router')->pathFor('admin.users.registration'));
        }
    }

    /**
     * logoutProcess
     */
    public function logoutProcess($response)
    {
        Session::destroy();
        return $response->withRedirect($this->container->get('router')->pathFor('admin.users.login'));
    }
}


class Users
{
    public static function isUsersExists() : bool
    {
        // Get Users Profiles
        $users = Filesystem::listContents(PATH['site'] . '/accounts/');

        // If any users exists then return true
        return ($users && count($users) > 0) ? true : false;
    }

    public static function isLoggedIn() : bool
    {
        return (Session::exists('role') && Session::get('role') == 'admin') ? true : false;
    }
}
