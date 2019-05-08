<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * 密码加密
 * @author Ultraman/2018-01-11
 * @param  string $pwd 明文密码
 * @return string      加密后的密码
 */
function encryptPwd($pwd)
{
    return think_ucenter_md5($pwd, config('UC_AUTH_KEY'));
}
/**
 * 系统非常规MD5加密方法
 * @param  string $str 要加密的字符串
 * @return string 加密后得到的字符串
 */
function think_ucenter_md5($str, $key = 'ThinkUCenter')
{
    return '' === $str ? '' : md5(sha1($str) . $key);
}

/**
 * 数据签名认证
 * @param  array  $data 被认证的数据
 * @return string       签名
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function data_auth_sign($data)
{
    //数据类型检测
    if (!is_array($data)) {
        $data = (array) $data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0, $adv = false)
{
    $type      = $type ? 1 : 0;
    static $ip = null;
    if ($ip !== null) {
        return $ip[$type];
    }
    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }

            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function format_bytes($size, $delimiter = '')
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) {
        $size /= 1024;
    }
    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function ext_list_to_tree($list, $pk='id', $pid = 'pid', $child = 'children', $root = 0,$type=true) {
    // 创建Tree
    $tree = array();
    if(is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        // 判断是否子集
        $childId = array();
        foreach ($list as $key => $value) {
            $childId[] = $value[$pid];
        }
        $childId = array_unique($childId);
        foreach ($list as $key => &$data) {
            // 判断是否存在parent
            $parentId =  $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            }else{
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
            if(in_array($data[$pk],$childId)){
                $data['leaf'] = false;
            }else{
                if($type ==true){
                    $data['children'] = array();
                } else {
                    $data['leaf'] = true;
                }
            }
        }
    }
    return $tree;
}

/**
 * 数字转中文大写
 * @param number $num 数字金额
 */
function num_to_cny($num=0){
    $c1 = "零壹贰叁肆伍陆柒捌玖";
    $c2 = "分角元拾佰仟万拾佰仟亿";
    //精确到分后面就不要了，所以只留两个小数位
    $num = round($num, 2);
    //将数字转化为整数
    $num = $num * 100;
    if (strlen($num) > 10) {
        return "金额太大，请检查";
    }
    $i = 0;
    $c = "";
    while (1) {
        if ($i == 0) {
            //获取最后一位数字
            $n = substr($num, strlen($num)-1, 1);
        } else {
            $n = $num % 10;
        }
        //每次将最后一位数字转化为中文
        $p1 = substr($c1, 3 * $n, 3);
        $p2 = substr($c2, 3 * $i, 3);
        if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
            $c = $p1 . $p2 . $c;
        } else {
            $c = $p1 . $c;
        }
        $i = $i + 1;
        //去掉数字最后一位了
        $num = $num / 10;
        $num = (int)$num;
        //结束循环
        if ($num == 0) {
            break;
        }
    }
    $j = 0;
    $slen = strlen($c);
    while ($j < $slen) {
        //utf8一个汉字相当3个字符
        $m = substr($c, $j, 6);
        //处理数字中很多0的情况,每次循环去掉一个汉字“零”
        if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
            $left = substr($c, 0, $j);
            $right = substr($c, $j + 3);
            $c = $left . $right;
            $j = $j-3;
            $slen = $slen-3;
        }
        $j = $j + 3;
    }
    //这个是为了去掉类似23.0中最后一个“零”字
    if (substr($c, strlen($c)-3, 3) == '零') {
        $c = substr($c, 0, strlen($c)-3);
    }
    //将处理的汉字加上“整”
    if (empty($c)) {
        return "零元整";
    } else if(substr($c, strlen($c)-3, 3) == '元'){
        return $c . "整";
    } else {
        return $c;
    }
}

/**

* 
* 把数字1-1亿换成汉字表述，如：123->一百二十三
* @param [num] $num [数字]
* @return [string] [string]
*/

function numToWord($num)
{
    $chiNum = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
    $chiUni = array('','十', '百', '千', '万','十', '百', '千', '亿', '十', '百','千','万','十', '百', '千');
    $uniPro = array(4, 8);
    $chiStr = '';


    $num_str = (string)$num;

    $count = strlen($num_str);
    $last_flag = true; //上一个 是否为0
    $zero_flag = true; //是否第一个
    $temp_num = null; //临时数字
    $uni_index = 0;

    $chiStr = '';//拼接结果
    if ($count == 2) {//两位数
        $temp_num = $num_str[0];
        $chiStr = $temp_num == 1 ? $chiUni[1] :                  $chiNum[$temp_num].$chiUni[1];
        $temp_num = $num_str[1];
        $chiStr .= $temp_num == 0 ? '' : $chiNum[$temp_num]; 
    }else if($count > 2){
        $index = 0;
        for ($i=$count-1; $i >= 0 ; $i--) { 
            $temp_num = $num_str[$i];
            if ($temp_num == 0) {
                $uni_index = $index%15;
                if ( in_array($uni_index, $uniPro)) {
                    $chiStr = $chiUni[$uni_index]. $chiStr;
                    $last_flag = true;
                }else if (!$zero_flag && !$last_flag ) {
                   $chiStr = $chiNum[$temp_num]. $chiStr;
                   $last_flag = true;
                }
            }else{
                $chiStr = $chiNum[$temp_num].$chiUni[$index%16] .$chiStr;

               $zero_flag = false;
               $last_flag = false;
            }
           $index ++;
         }
    }else{
        $chiStr = $chiNum[$num_str[0]]; 
    }
    return $chiStr;
}

/**
 * [getUniqueNum 获取唯一编号]
 * @param  [type] $num [description]
 * @return [type]      [description]
 */
function getUniqueNum($num=1){
    $abc = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
    $id = db('research')->where('project_id',$num)->order('id desc')->value('id');
    $id = $id ? $id : 1;
    return $abc[$num%26].$abc[rand(0,25)].$abc[rand(0,25)].$abc[rand(0,25)]."-".str_pad($id,9,'0',STR_PAD_LEFT);
}


/**
 * [getAgeByIdcard 身份证获取年龄性别]
 * @param  [type] $idcard [description]
 * @return [type]         [description]
 */
function getAgeByIdcard($idcard){
    //通过身份证号查询出性别与生日 
    $birth = strlen($idcard)==15 ? ('19' . substr($idcard, 6, 6)) : substr($idcard, 6, 8); 
    $year = substr($birth,0,4);
    $month = substr($birth,4,2);
    $day = substr($birth,6,2);
    $age = 0;
    if(date("m") > $month){
        $age = date("Y") - $year;
    }else if(date("m")  == $month){
        $age = date("d") >= $day ? date("Y") - $year : date("Y") - $year - 1;   
    }else{
        $age = date("Y") - $year - 1;
    }
    $sex = substr($idcard, (strlen($idcard)==15 ? -1 : -2), 1) % 2 ? 1 : 2; //1为男 0为女
    return array('age'=>$age,'sex'=>$sex,'birthday_time'=>$year.":".$month.":".$day);
}

/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true) {
    if(function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif(function_exists('iconv_substr')) {
        $slice = iconv_substr($str,$start,$length,$charset);
        if(false === $slice) {
            $slice = '';
        }
    }else{
        $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("",array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice.'...' : $slice;
}

/**
 * [getSetting 配置转换]
 * @param  [type] $index      [description]
 * @param  [type] $name       [description]
 * @param  string $configFile [description]
 * @return [type]             [description]
 */
function getSetting($index,$name,$configFile='collection'){
    if(isset(config($configFile.'.'.$name)[$index])){
        return config($configFile.'.'.$name)[$index];
    }
    return '暂无';
}
