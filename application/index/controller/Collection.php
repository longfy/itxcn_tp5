<?php
/**
 * 我的收藏
 */
namespace app\index\controller;

use think\Request;

class Collection extends Common{
    //添加收藏
    public function add(){
        $id = input('id');
        if(!$id){
            return ['status' => false, 'msg' => '文章id不能为空！'];
        }
        if(db('collection')->where(array('user_id'=>$this->getUserId(),'article_id'=>$id))->count()>0){
            return ['status' => false, 'msg' => '文章已收藏！'];
        }
        $add = db('collection')->insert(array(
            'user_id'       => $this->getUserId(),
            'article_id'    => $id,
			'create_time'	=>	date('Y-m-d H:i:s'),
        ));
        return getReturn(0,$add);
    }
    //取消收藏
    public function delete(){
        $id = input('id');
        if(!$id){
            return ['status' => false, 'msg' => 'id不能为空！'];
        }
        if(db('collection')->where('article_id',$id)->count()==0){
            return ['status' => false, 'msg' => '数据不存在！'];
        }
        $delete = db('collection')->where('id',$id)->delete();
        return getReturn(1,$delete);
    }
    //我的收藏列表
    public function getList(){
        $list = array();
        $query = db('collection')->where('user_id',$this->getUserId())->select();
        if(!empty($query)) {
            foreach($query as $key => $value) {
                $hasCount = db('article')->where('id',$value['article_id'])->count();
                if($hasCount) {
                    array_push($list, db('article')->where('id',$value['article_id'])->select()[0]);
                }
            }
        }
        return $list;
    }
}