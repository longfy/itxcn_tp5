<?php
/**
 * 文章管理
 */
namespace app\index\controller;

use Think\Db;
use Think\File;

class Article extends Common {
    //添加
    public function add(){
        $title = input('title');
		$content = input('content');
		$img_id = input('img_id');
		if(!$title){
			return ['status' => false, 'msg' => '文章标题不能为空！'];
		}
		if(!$content){
			return ['status' => false, 'msg' => '文章内容不能为空！'];
		}
		$add = db('article')->insert(array(
			'title'			=>	$title,
			'content'		=>	$content,
			'img_id'		=>	$img_id,
			'handle_id'		=>	$this->getUserId(),
			'create_time'	=>	date('Y-m-d H:i:s'),
		));
		return $this->getReturn(0,$add);
    }
    //编辑
    public function edit(){
        $id = input('id');
        $title = input('title');
		$content = input('content');
		$img_id = input('img_id');
		if(!$id){
			return ['status' => false, 'msg' => '文章id不能为空！'];
		}
		if(!$title){
			return ['status' => false, 'msg' => '文章标题不能为空！'];
		}
		if(!$content){
			return ['status' => false, 'msg' => '文章内容不能为空！'];
		}
		if(!db('article')->where('id',$id)->count()){
            return ['status' => false, 'msg' => '数据不存在！'];
        }
        $update = db('article')->where('id',$id)->update(array(
			'title'			=>	$title,
			'content'		=>	$content,
			'img_id'		=>	$img_id,
		));
		return $this->getReturn(2, $update);
    }
    //删除
    public function delete(){
        $id = input('id');
        if(!db('article')->where('id',$id)->count()){
            return ['status' => false, 'msg' => '数据不存在！'];
        }
        $delete = db('article')->where('id',$id)->delete();
        return $this->getReturn(1,$delete);
    }
    //获取文章列表
    public function getList(){
        return db('article')->select();
    }
	//获取文章详情
    public function getDetails(){
       $id = input('id');
        if(!db('article')->where('id',$id)->count()){
            return ['status' => false, 'msg' => '数据不存在！'];
        }
        return $details = db('article')->find();
    }
}