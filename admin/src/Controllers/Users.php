<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Image;
use Formwork\Admin\Security\Password;
use Formwork\Admin\Uploader;
use Formwork\Admin\User;
use Formwork\Core\Formwork;
use Formwork\Data\DataGetter;
use Formwork\Parsers\YAML;
use Formwork\Router\RouteParams;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use RuntimeException;

class Users extends AbstractController
{
    public function index()
    {
        $this->modal('newUser');

        $this->modal('deleteUser');

        $this->view('admin', array(
            'title' => $this->label('users.users'),
            'content' => $this->view('users.index', array(
                'users' => Admin::instance()->users()
            ), false)
        ));
    }

    public function create()
    {
        $this->data = new DataGetter(HTTPRequest::postData());

        // Ensure no required data is missing
        foreach (array('username', 'fullname', 'password', 'email', 'language') as $var) {
            if (!$this->data->has($var)) {
                $this->notify($this->label('users.user.cannot-create.var-missing', $var), 'error');
                $this->redirect('/users/', 302, true);
            }
        }

        // Ensure there isn't a user with the same username
        if (Admin::instance()->users()->has($this->data->get('username'))) {
            $this->notify($this->label('users.user.cannot-create.already-exists'), 'error');
            $this->redirect('/users/', 302, true);
        }

        $userdata = array(
            'username' => $this->data->get('username'),
            'fullname' => $this->data->get('fullname'),
            'hash'     => Password::hash($this->data->get('password')),
            'email'    => $this->data->get('email'),
            'avatar'   => null,
            'language' => $this->data->get('language')
        );

        $fileContent = YAML::encode($userdata);

        FileSystem::write(ACCOUNTS_PATH . $this->data->get('username') . '.yml', $fileContent);

        $this->notify($this->label('users.user.created'), 'success');
        $this->redirect('/users/', 302, true);
    }

    public function delete(RouteParams $params)
    {
        try {
            $user = Admin::instance()->users()->get($params->get('user'));
            if (!$user) {
                throw new RuntimeException($this->label('users.user.not-found'));
            }
            if ($user->logged()) {
                throw new RuntimeException($this->label('users.user.cannot-delete.logged'));
            }
            $this->deleteAvatar($user);
            FileSystem::delete(ACCOUNTS_PATH . $params->get('user') . '.yml');
            $this->registry('lastAccess')->remove($params->get('user'));
            $this->notify($this->label('users.user.deleted'), 'success');
            $this->redirect('/users/', 302, true);
        } catch (RuntimeException $e) {
            $this->notify($e->getMessage(), 'error');
            $this->redirect('/users/', 302, true);
        }
    }

    public function profile(RouteParams $params)
    {
        $user = Admin::instance()->users()->get($params->get('user'));

        if (is_null($user)) {
            $this->notify($this->label('users.user.not-found'), 'error');
            $this->redirect('/users/', 302, true);
        }

        if (HTTPRequest::method() === 'POST') {
            $data = $user->toArray();

            $postData = HTTPRequest::postData();

            unset($postData['csrf-token']);

            if (!empty($postData['password'])) {
                $postData['hash'] = Password::hash($postData['password']);
                unset($postData['password']);
            }

            foreach ($postData as $key => $value) {
                if (!empty($value)) {
                    $data[$key] = $value;
                }
            }

            if (HTTPRequest::hasFiles()) {
                $avatarsPath = ADMIN_PATH . 'avatars' . DS;
                $uploader = new Uploader(
                    $avatarsPath,
                    array('allowedMimeTypes' => array('image/gif', 'image/jpeg', 'image/png'))
                );
                try {
                    if ($uploader->upload(str_shuffle(uniqid()))) {
                        $avatarSize = Formwork::instance()->option('admin.avatar_size');
                        $image = new Image($avatarsPath . $uploader->uploadedFiles()[0]);
                        $image->square($avatarSize)->save();
                        $this->deleteAvatar($user);
                        $data['avatar'] = $uploader->uploadedFiles()[0];
                        $this->notify($this->label('user.avatar.uploaded'), 'success');
                    }
                } catch (RuntimeException $e) {
                    $this->notify($this->label('uploader.error', $e->getMessage()), 'error');
                    $this->redirect('/users/' . $user->username() . '/profile/', 302, true);
                }
            }

            $fileContent = YAML::encode($data);

            FileSystem::write(ACCOUNTS_PATH . $data['username'] . '.yml', $fileContent);

            $this->notify($this->label('users.user.edited'), 'success');
            $this->redirect('/users/' . $user->username() . '/profile/', 302, true);
        }

        $this->modal('changes');

        $this->view('admin', array(
            'title' => $this->label('users.user-profile', $user->username()),
            'content' => $this->view('users.profile', array(
                'user' => $user
            ), false)
        ));
    }

    protected function deleteAvatar(User $user)
    {
        $avatar = $user->avatar()->path();
        if (FileSystem::exists($avatar)) {
            FileSystem::delete($avatar);
        }
    }
}
