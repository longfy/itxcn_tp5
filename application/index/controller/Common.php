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

class Common extends Controller{
	
    private $userId;
    //所有控制器类继承了\think\Controller类的话，可以定义控制器初始化方法_initialize，在该控制器的方法调用之前首先执行
    protected function _initialize()
    {
        $this->userId = session('user.id');
    }

    //上传图片
    public function uploads(){
        // 获取表单上传文件
        $file = request()->file('file');
        $uploadPath = config('upload_path.file_path');
        $type       = config('upload_file_type');
        $size       = config('upload_file_size');
        if($file){
            //验证、上传文件
            $info = $file->validate(['size' => $size, 'ext' => $type])->move(ROOT_PATH . 'public' . DS . $uploadPath, true, false);
            if($info){
                $fileName = $file->getInfo()['name']; // php.ini 配置文件下 ;extension=php_fileinfo.dll，去掉分号
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
     * [lists 列表数据]
     * @param  [type]  $model       [模型数据]
     * @param  array   $searchWhere [搜索条件]
     * @param  boolean $field       [查询字段]
     * @param  string  $order       [排序]
     * @param  [type]  $group       [分组]
     * @param  boolean $is_page     [分页]
     * @param  [type]  $having      [having]
     * @return [type]               [description]
     */
    protected function lists($model, $searchWhere = array(), $field = true, $order = '', $group = null, $is_page = true, $having = null)
    {
        if (is_string($model)) {
            $model = M($model);
        }
        //接收所有的数据
        $param = input(); // 获取通用条件,分页
        $start = isset($param['start']) ? intval($param['start']) : 0;
        $limit = isset($param['limit']) ? intval($param['limit']) : 25;
        // 条件模式
        // $searchWhere = [
        //     'a.name|a.idcard' => array('like','search')
        //     'a.pro_id' => array('exp',' IS NOT NULL'),
        //     'a.time' => array('between',['begin_time','end_time']),
        //     'a.id' => array('eq','id'),
        // ];

        //遍历 searchWhere  检测有没有模糊搜索
        foreach ($searchWhere as $k => $v) {
            if (!is_array($v)) {
                continue;
            }
            $exp = strtolower(trim($v[0]));
            switch ($exp) {
                case 'like':
                    $search = isset($param[$v[1]]) ? $param[$v[1]] : '';
                    if (!empty($search)) {
                        $model->where(function ($query) use ($k, $search) {
                            $query->where(array($k => array('like', '%' . $search . '%')));
                        });
                    }
                    break;
                case 'between':
                    $sectorArr  = $v[1];
                    $start_time = isset($param[$sectorArr[0]]) ? date('Y-m-d', strtotime($param[$sectorArr[0]])) . ' 00:00:00' : '';
                    $end_time   = isset($param[$sectorArr[1]]) ? date('Y-m-d', strtotime($param[$sectorArr[1]])) . ' 23:59:59' : '';
                    if ($start_time == "" && $end_time != "") {
                        $model->where($k, 'ELT', $end_time);
                    } elseif ($start_time != "" && $end_time == "") {
                        $model->where($k, 'EGT', $start_time);
                    } elseif ($start_time != "" && $end_time != "") {
                        $model->where($k, 'between', array($start_time, $end_time));
                    }
                    break;
                case 'exp':
                    if (!empty($v[1])) {
                        $model->where($k, 'exp', $v[1]);
                    }
                    break;
                default:
                    $search = isset($param[$v[1]]) && $param[$v[1]] ? $param[$v[1]] : '';
                    $see    = $v[0];
                    if (!empty($search)) {
                        $model->where(function ($query) use ($k, $see, $search) {
                            $query->where(array($k => array($see, $search)));
                        });
                    }
                    break;
            }
        }
        //保存模型中现有的options
        $options = $model->getOptions();

        //获取模型中的主键
        $pk = $model->getPrimaryKey();
        //排序
        if ($order === null) {
            //order置空
        } elseif ($order === '' && empty($options['order']) && !empty($pk)) {
            if (is_array($pk)) {
                foreach ($pk as $k => $v) {
                    $options['order'][$k] = $v . ' desc';
                }
            } elseif (is_string($pk)) {
                $options['order'][] = $pk . ' desc';
            }
        } elseif ($order) {
            $options['order'] = $order;
        }

        //分组
        if ($group) {
            $options['group'] = $group;
        }
        //聚合条件
        if ($having) {
            $options['having'] = $having;
        }

        //过滤条件中的空值
        if (isset($options['where'])) {
            $options['where'] = array_filter((array) $options["where"], function ($val) {
                if ($val === '' || $val === null || (isset($val[1]) && ($val[1] === "" || $val[1] === null || (is_array($val[1]) && empty($val[1]))))) {
                    //if($val===''||$val===null || (trim($val[0])==="in" && empty($val[1]) )){//过滤掉不需要的请求参数，如:参数值为空
                    return false;
                } else {
                    return true;
                }
            });
            //使用 where
            if (empty($options['where'])) {
                unset($options['where']);
            }
        }
        // 设置条件参数
        $model->options($options);
        //分页处理
        $totalSql = '(' . $model->field($pk . ',0 as delete_time')->fetchSql(true)->select() . ')';
        $total    = db()->table($totalSql)->alias('t')->count(); //echo $model->getLastSql();echo '<br/>';
        //只把get放入分页参数中
        if ($is_page === true) {
            $options['limit'] = $start . ',' . $limit;
            // 设置查询分页数据参数
            $model->options($options);
        }
        if (!empty($field) && $field !== true) {
            $results = $model->field($field)->select();
        } else {
            $results = $model->select();
        } //echo $model->getLastSql();
        return ['data' => $results, 'total' => $total];
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