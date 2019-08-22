<?php
namespace app\index\controller;

//使用系统相关方法得继承系统控制器
namespace app\index\controller;

class Admin extends Common {
    //所有控制器类继承了\think\Controller类的话，可以定义控制器初始化方法_initialize，在该控制器的方法调用之前首先执行
    protected function _initialize()
    {
        if (!IS_CLI) {
            // 验证是否登录
            $this->checkLogin();
            // 检查用户权限
        }
    }

    /**
     * 登录
     */
    public function login()
    {
		$param['userName'] = input('userName');
        $param['password'] = input('password');
		$param['userType'] = 1;
		return logic('IndexLogic/login',$param);
    }

	/**
     * 验证用户是否登录
     * @author Ultraman/2018-06-01
     * @return void
     */
    protected function checkLogin()
    {
        if (!(new Index)->checkLogin()) {
            $request = request();
            if ($request->isAjax()) {
                if ($request->isGet()) {
                    $response = array();
                } else {
                    $response = array('status' => false, 'msg' => '对不起，您没有登录，请先登录！');
                }
                Response::create($response, 'json')->send();
                exit();
            } else {
                $type_view = $this->request->param("type_view",null);
                if(empty($type_view)){
                    $this->error('对不起，您没有登录，请先登录！');
                }

            }
        }
    }
}
