<?php 
namespace app\index\logic;

/**
 * 业务层Index处理
 * 2019/05/29
 */
class IndexLogic extends CommonLogic
{
	/**
	 * [login 登录]
	 * @param  [type] $info [description]
	 * @return [type]       [description]
	 */
	public function login($info){
		$userName = $info['userName'];
		$password = $info['password'];
		$userType = $info['userType'];
		$user = db('users')->where(array('account' => $userName, 'user_type' => $userType))->find();
		if (!empty($user)) {
			if ($user['remain'] == 0) {
				return array('status' => false, 'msg' => '该账号已被锁定，请联系管理员解锁！');
			} else if ($user['password'] == encryptPwd($password)) {
				//保存session
				session('user.id', $user['id']);
				session('user.name', $user['name']);
				session('user.user_type', $user['user_type']);
				//重置登录错误次数
                db('users')->where(array('id' => $user['id']))->update(array('remain' => 5));
                $userInfo['id'] = $user['id'];
                $userInfo['name'] = $user['name'];
                $userInfo['account'] = $user['account'];
				return array('status' => true, 'msg' => '登录成功！', 'userInfo' => $userInfo);
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
	 * [loginOut 退出]
	 * @return [type] [description]
	 */
	public function loginOut(){
		//logic('LogLogic/log', 2);
        session(null);
        return msgReturn('退出成功！', true);
	}
	
}