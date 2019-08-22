<?php
/**
 * 用户管理模块
 */
namespace app\index\controller;

class Users extends Common{

    /**
     * 获取人员列表
     * @return [type] [description]
     */
    public function getList(){
        return logic('UserLogic/getUsersList');
    }

    /**
     * 新增
     * @return [type] [description]
     */
    public function add(){
        $name = input('name');
        $account = input('account');
        $password = input('password');
        if(!$name){
            return ['status' => false, 'msg' => '姓名不能为空！'];
        }
        if(!$account){
            return ['status' => false, 'msg' => '账号不能为空！'];
        }
        if(!$password){
            return ['status' => false, 'msg' => '密码不能为空！'];
        }
        $unique = logic('UserLogic/checkAccountIsUnique', ['account' => $account]);
        if (!$unique) {
            return array('status' => false, 'code' => 1, 'msg' => '该账号已存在，请重新填写账号！');
        }
        $add = db('users')->insert(array(
            'name'      => $name,
            'account'   => $account,
            'password'  => encryptPwd($password),
        ));
        return getReturn(0, $add);
    }

    /**
     * 编辑
     * @return [type] [description]
     */
    public function edit(){
        $name = input('name');
        $account = input('account');
        $old_password = input('old_password');
        $new_password = input('new_password');
        if(!$name){
            return ['status' => false, 'msg' => '姓名不能为空！'];
        }
        if(!$account){
            return ['status' => false, 'msg' => '账号不能为空！'];
        }
        if(!$old_password){
            return ['status' => false, 'msg' => '密码不能为空！'];
        }
        if(!$new_password){
            return ['status' => false, 'msg' => '新密码不能为空！'];
        }
        $db_password = db('users')->where('account',$account)->value('password');
        if(!$db_password){
            return ['status' => false, 'msg' => '用户名不存在！'];
        }
        if(encryptPwd($old_password) != $db_password) {
            return ['status' => false, 'msg' => '原密码不正确！'];
        }
        $update = db('users')->where('account',$account)->update(array(
            'name'=> $name,
            'password'  => encryptPwd($new_password),
        ));
        return getReturn(2, $update);
    }

    /**
     * 删除
     * @return [type] [description]
     */
    public function delete(){
        $params['account'] = input('account');
        $params['password'] = input('password');
        logic('UserLogic/delUser', $params);
    }
}