<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

namespace think;
// $origin = $_SERVER['HTTP_ORIGIN'];
// header("Access-Control-Allow-Origin:".$origin); //允许通过的域名，这里直使用通用方法接获取前端域名
// header('Access-Control-Allow-Credentials:true'); //表示是否允许发送Cookie
// header("Access-Control-Allow-Methods:GET, POST, OPTIONS, DELETE"); //允许的请求方式
// header("Access-Control-Allow-Headers:token, Origin, X-Requested-With, Content-Type, Accept"); // 允许访问的表头名称

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
