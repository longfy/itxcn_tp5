<?php 
namespace app\index\logic;

use think\Model;

/**
 * 业务逻辑处理
 * 2019/05/29
 */
class CommonLogic extends Model
{
	/**
	* 获取用户昵称
	* @date: 2019年5月31日 下午5:22:27
	* @author: lfy
	*/

	public function getUserName(){
		return db('users')->where('id',session('user.id'))->value('name');
	}
}