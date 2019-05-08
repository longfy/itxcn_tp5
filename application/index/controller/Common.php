<?php
/**
 * 公共控制器
 */

namespace app\index\controller;

//使用系统相关方法得继承系统控制器
use think\Controller;
use think\Request;
use think\Response;

class Common{
	
    private $userId;
    //所有控制器类继承了\think\Controller类的话，可以定义控制器初始化方法_initialize，在该控制器的方法调用之前首先执行
    protected function _initialize()
    {
        if (!IS_CLI) {
            ///////////
            //验证是否登录 //
            ///////////
            //$this->checkLogin();
            ///////////
            //检查用户权限 //
            ///////////
        }
        //$this->userId               = session('user.id');
    }
	
	//上传图片
    public function uploads(){
        // 获取表单上传文件
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 jpg
                //echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                //echo $info->getSaveName();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
                //echo $info->getFilename(); 
				$path = $info->getSaveName();
            }else{
                // 上传失败获取错误信息
                //echo $file->getError();
				return array('status' => false, 'msg' => $file->getError());
            }
			return array('status' => true, 'path' => $path);
        }
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
	/**
     * 得到用户id
     * @author Ultraman/2018-11-23
     */
    public function getUserId()
    {
        return empty($this->userId) ? ($this->userId = session('user.id')) : $this->userId;
    }
    /**
     * 返回操作状态
     * @author Ultraman/2018-06-15
     * @param  int $code   操作码：0、添加；1、删除；2、编辑；3、操作
     * @param  bool $status 操作结果
     * @return array         操作状态数组
     */
    protected function getReturn($code, $status)
    {
        $status  = $status !== false;
        $codeMsg = array(
            0 => array(1 => '添加成功！', 0 => '添加失败，请稍后重试！'),
            1 => array(1 => '删除成功！', 0 => '删除失败，请稍后重试！'),
            2 => array(1 => '编辑成功！', 0 => '编辑失败，请稍后重试！'),
            3 => array(1 => '操作成功！', 0 => '操作失败，请稍后重试！'),
        );
        return array('status' => $status, 'msg' => $codeMsg[$code][intval($status)]);
    }

    /**
     * [msgReturn 简单的提示返回]
     * @param  [type] $status [description]
     * @param  [type] $msg    [description]
     * @return [type]         [description]
     */
    protected function msgReturn($msg,$status=false){
        return array('status'=>$status,'msg'=>$msg);
    }
	
	/**
	 * 兼容移动端与Web端统一返回
	 *
	 * @param        $type
	 * @param array  $data
	 * @param bool   $status
	 * @param int    $code
	 * @param string $msg
	 * @param int    $total
	 *
	 * @return array
	 *
	 * @author Umbrella_J
	 */
    protected function unifiedReturn($type, $data = [], $status = true, $code = 100, $msg = '', $total = 0) {
    	// 处理来自移动端请求
    	if (request()->isMobile()) {
			return array('status' => $status, 'msg' => $msg, 'data' => $data);
		} else {
    		switch ($type) {
				case 1:	// web 端列表返回结构
					return array('data' => $data, 'total' => $total);
					break;
				case 2: // web 端详情返回结构
					return $data;
					break;
				case 3: // web 端操作返回结构
					return array('status' => $status, 'msg' => $msg);
			}
		}
	}
}