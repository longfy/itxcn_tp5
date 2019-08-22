<?php
namespace app\index\behavior;
use think\Response;

class Cors {

    public function appInit(){
    	$origin = request()->server('HTTP_ORIGIN') ?: '';
    	$allow_origin = [
			'http://127.0.0.1',
            'http://localhost:8080',
			'http://itxcn.cn',
			'http://longfeiyang.com.cn',
		];
        if (in_array($origin, $allow_origin)) {
	        header('Access-Control-Allow-Origin:'.$origin);
	        header('Access-Control-Allow-Headers: token, Origin, X-Requested-With, Content-Type, Accept, Authorization');
	        header('Access-Control-Allow-Methods: POST,GET,PUT,DELETE,OPTIONS');
			header('Access-Control-Allow-Credentials: true'); //表示是否允许发送Cookie
        }
        if(request()->isOptions()){
            exit();
        }
    }

}
