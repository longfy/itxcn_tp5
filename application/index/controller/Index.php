<?php
namespace app\index\controller;
use think\captcha\Captcha;
use think\Session;

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
		$param['userName'] = input('userName');
        $param['password'] = input('password');
		$param['userType'] = 0;
		return logic('IndexLogic/login',$param);
    }
    /**
     * 验证用户是否登录
     */
    public function checkLogin()
    {
        if (empty(session('user'))) {
            return false;
        } else {
            return ['status' => true, 'user' => session::get('user')];
        }
    }
    /* 退出登录 */
    public function logout()
    {
        return logic('IndexLogic/LoginOut');
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
