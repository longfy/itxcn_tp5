<?php
namespace app\index\controller;
use think\captcha\Captcha;

class Index extends Common{
    /**
     * 首页
     */
    public function index()
    {
        //return ('./login.html');
    }
    /**
     * 登录
     */
    public function login()
    {
        $user = db('users')->where(array('account' => input('userName')))->find();
		if (!empty($user)) {
			if ($user['remain'] == 0) {
				return array('status' => false, 'msg' => '该账号已被锁定，请联系管理员解锁！');
			} else if ($user['password'] == encryptPwd(input('password'))) {
				//保存session
				session('user.id', $user['id']);
				//重置登录错误次数
				db('users')->where(array('id' => $user['id']))->update(array('remain' => 5));
				return array('status' => true, 'msg' => '登录成功！');
			} else {
				db('users')->where(array('id' => $user['id']))->update(['remain' => array('dec', '1')]);
				if ($user['remain'] == 1) {
					$error = '登录失败，用户名或密码错误！该账号已被锁定，请联系管理员解锁！';
				} else if ($user['remain'] <= 4) {
					$error = '登录失败，用户名或密码错误！您还有' . ($user['remain'] - 1) . '次机会，系统将会锁定账号！';
				} else {
					$error = '登录失败，用户名或密码错误！';
				}
				return array('status' => false, 'msg' => $error);
			}
		} else {
			return array('status' => false, 'msg' => '登录失败，用户名或密码错误！');
		}
    }
    /**
     * 验证用户是否登录
     */
    public function checkLogin()
    {
        if (empty(session('user'))) {
            return false;
        } else {
            return true;
        }
    }
    /* 退出登录 */
    public function logout()
    {
        session(null);
        return array('status' => true, 'msg' => '退出成功！');
    }
    /**
     * 输出验证码图片
     */
    public function verify()
    {
        $captcha = new Captcha(array(
            'length'   => 4,
            'useCurve' => false,
            'useNoise' => false,
        ));
        return $captcha->entry(1);
    }
}
