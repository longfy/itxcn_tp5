<?php
/**
 * 导航菜单
 */
namespace app\index\controller;

class Navs extends Common{

    public function getNavsMenu(){
        $search = input('id');
        if(empty($search)) {
            return $this->getChildTreeNodesByPid('0');
        } else {
            return $this->getChildTreeNodesByPid($search);
        }
    }

    protected function getChildTreeNodesByPid($pid){
        $childs = array();
        $query = db('navs')->where(array('pid' => $pid, 'hidden' => 0))->field(array('id', 'name', 'url'))->order('sort')->select();
        if(!empty($query)) {
            foreach($query as $key => $value) {
                $hasChild = db('navs')->where(array('pid' => $value['id'], 'hidden' => 0))->count();
                if($hasChild) {
                    array_push($childs, array(
                        'id'        => $value['id'],
                        'name'      => $value['name'],
                        'url'  		=> $value['url'],
                        'children'  => $this->getChildTreeNodesByPid($value['id']),
                    ));
                } else {
                    array_push($childs, array(
                        'id'    => $value['id'],
                        'name'  => $value['name'],
                        'url'   => $value['url'],
                        'leaf'  => true,
                    ));
                }
            }
        }
        return $childs;
    }

    //新增栏目
    public function add(){
        $pid = input('pid');
        $name = input('name');
        if(empty($name)) {
            return ['status' => false,'msg' => '栏目名称不能为空！'];
        }
        if(empty($pid)) {
            $pid = 0;
        }
        $add = db('navs')->insert(array(
            'pid'           => $pid,
            'name'          => input('name'),
            'create_time'   => date('Y-m-d H:i:s'),
        ));
        return $this->getReturn(0, $add);
    }

    //编辑栏目
    public function edit(){
        $id = input('id');
        $name = input('name');
        if(empty($name)) {
            return ['status' => false, 'msg' => '栏目名称不能为空！'];
        }
        if(!db('navs')->where('id',$id)->count()){
            return ['status' => false, 'msg' => '数据不存在！'];
        }
        $update = db('navs')->where('id',$id)->update(array('name'=> $name));
        return $this->getReturn(2, $update);
    }

    //删除栏目
    public function delete() {
        $id = input('id');
        if(!db('navs')->where('id',$id)->count()){
            return ['status' => false, 'msg' => '数据不存在！'];
        }
        $delete = db('navs')->where('id',$id)->delete();
        return $this->getReturn(1,$delete);
    }
}