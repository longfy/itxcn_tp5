<?php
namespace app\index\logic;

/**
 * 用户管理业务处理层
 * 2019/05/29
 */
class UserLogic extends CommonLogic
{
    /**
     * [getUsersList 获取人员列表]
     * @return [type] [description]
     */
    public function getUsersList()
    {
        return db('users')->field(['id', 'name', 'account'])->select();
    }


    /**
     * [delUser 删除人员]
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function delUser($param){
        $account = $param['account'];
        $password = $param['password'];
        if(!$account){
            return ['status' => false, 'msg' => '账号不能为空！'];
        }
        if(!$password){
            return ['status' => false, 'msg' => '密码不能为空！'];
        }
        $db_password = db('users')->where('account',$account)->value('password');
        if(!$db_password){
            return ['status' => false, 'msg' => '用户名不存在！'];
        }
        if(encryptPwd($password) != $db_password) {
            return ['status' => false, 'msg' => '密码不正确！'];
        }
        $delete = db('users')->where('account',$account)->delete();
        return getReturn(1,$delete);
    }

    /**
     * 验证账号是否唯一
     * @author Ultraman/2018-05-25
     * @param  string $account 账号
     * @return bool          验证结果
     */
    function checkAccountIsUnique($params, $id = 0)
    {
        return db('users')->where(array('account' => $params['account'], 'id' => array('NEQ', $id)))->count() == 0;
    }

    /**
     * [isAdmin 是否是管理员]
     * @return boolean [description]
     */
    public function isAdmin($params){
    	return db('users')->where('id', $params['uid'])->value('is_administrator') === 2;
    }
}
