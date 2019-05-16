<?php
/**
 * 公共控制器
 */

namespace app\index\controller;

//使用系统相关方法得继承系统控制器
use think\Db;
use think\File;
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
        $uploadPath = config('upload_path.file_path');
        $type       = config('upload_file_type');
        $size       = config('upload_file_size');
        $fileName   = $file->getInfo()['name'];
        if($file){
            //验证、上传文件
            $info = $file->validate(['size' => $size, 'ext' => $type])->move(ROOT_PATH . 'public' . DS . $uploadPath, true, false);
            if($info){
                $saveName = $info->getFilename();
                $savepath = substr($info->getSaveName(), 0, strpos($info->getSaveName(), DS));
                $filePath = ROOT_PATH . 'public' . DS . $uploadPath . DS . $info->getSaveName();
                // 成功上传后 获取上传信息
                $fileData = [
                    'name'        => $fileName,
                    'savename'    => $saveName,
                    'savepath'    => $savepath,
                    'type'        => $info->getExtension(),
                    'mime'        => $info->getMime(),
                    'size'        => $info->getSize(),
                    'md5'         => $info->md5(),
                    'sha1'        => $info->sha1(),
                    'is_use'      => 0,
                    'create_time' => date('Y-m-d H:i:s'),
                ];
                Db::startTrans();
                $flagA  = Db::table('attachment')->insertGetId($fileData);
                if ($flagA) {
                    Db::commit();
                    $attachment = db('attachment')->where(array('savename' => $saveName))->find();
                    return ['status' => true, 'msg' => '上传成功！', 'saveName' => $saveName, 'savepath' => $savepath, 'attachment_id' => $attachment['id']];
                } else {
                    Db::rollback();
                    return ['status' => false, 'msg' => '上传失败！', 'fileName' => $fileName, 'filePath' => $filePath];
                }
            }else{
                // 上传失败获取错误信息
                return ['status' => false, 'msg' => $file->getError(), 'fileName' => $fileName];
            }
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
