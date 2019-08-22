<?php
namespace app\index\logic;
use think\Image;
/**
 * Class UploadFileService
 * @package app\common\service
 * 附件上传
 */
class UploadFileService{
    protected $errorMsg=array();  //错误记录
    protected $errorNum; //错误数量
    protected $totalNum; //总数量
    protected $fileId=array(); //返回附件ID
    protected $visit_path=array();
    protected $delSuccFile=array(); //成功删除本地附件的ID
    protected $uploadPath=ROOT_PATH."public/";
    /**
     * @param int $data_from 0 模型上传 1 非模型上传（头像等） 2特殊表上传 folder # folder_file_r
     * @param $files
     * @param array $extraData
     * @return array
     * 附件上传
     */
    public function upload($data_from=0,$files,$extraData=array()){
        $upload_config=config("upload");
        if(!empty($files)){
            $this->totalNum=count($files);
            foreach ($files as $fileData){
                /*if(is_array($fileData)){ //多个上传
                    $this->totalNum=count($fileData);
                    foreach ($fileData as $fileObj){
                        if($data_from==0) {
                            $this->modelUpload($fileObj, $upload_config, $extraData);
                        }elseif($data_from==1){
                            //独立上传
                            $this->independentUpload($fileObj,$upload_config,$extraData);
                        }else{
                            $this->specialUpload($fileObj,$upload_config,$extraData);
                        }
                    }
                }else{ //单个上传
                    $this->totalNum=1;
                    $fileObj=$fileData;
                    if($data_from==0) {
                        $this->modelUpload($fileObj, $upload_config, $extraData);
                    }elseif($data_from==1){
                        $this->independentUpload($fileObj,$upload_config,$extraData);
                    }else{
                        $this->specialUpload($fileObj,$upload_config,$extraData);
                    }
                }*/
                if($data_from==0) {
                    $this->modelUpload($fileData, $upload_config, $extraData);
                }elseif($data_from==1){
                    //独立上传
                    $this->independentUpload($fileData,$upload_config,$extraData);
                }else{
                    $this->specialUpload($fileData,$upload_config,$extraData);
                }

            }
        }else{
            return array(
                "status"=>false,
                "msg"=>"请上传相关文件！"
            );
        }
        if(($this->totalNum)==($this->errorNum)){
            return array(
                "status"=>false,
                "msg"=>$this->errorMsg
            );
        }else{
            return array(
                "status"=>true,
                "data"=> array(
                    'msg'=>$this->errorMsg,
                    'file_id'=>array_unique($this->fileId),
                    'path'=>array_unique($this->visit_path)
                )
            );
        }
    }

    /**
     * @param $fileObj
     * @param $upload_config
     * @param $extraData
     * @return array|bool|int
     * 模型上传
     */
    private function modelUpload($fileObj,$upload_config,$extraData){
        $uploadPath = $upload_config["upload_path"];
        $fileInfo= $fileObj->getInfo(); //获取附件信息
        $file_sha1= sha1_file($fileInfo['tmp_name']);
        $file_md5 = md5_file($fileInfo['tmp_name']);

        //验证附件是否符合条件
        $validate=$this->validateFile($fileObj,$upload_config);
        if(empty($validate)){
            return $validate;
        }
        $attachmentData = [
            'file_id' => 0,
            'file_name' => "",
            'size' => $fileObj->getSize(),
            'create_time' => date('Y-m-d H:i:s'),
            'attachment_model_id' => empty($extraData['model_id']) ? null : $extraData['model_id'],
            'attachment_folder_id' => isset($extraData['folder_id'])?$extraData['folder_id']:0,
            'target_id' => $extraData['target_id'],
            'width' => 0,
            'height' => 0,
            'isImg' => false,
            'uid' => $extraData["uid"],
        ];
        //文件已存在
        $old_file_data=db("file")->where(
            array(
                "md5"=>$file_md5,
                "data_from"=>$extraData["data_from"],
                "uid"=>$extraData["uid"]
            )
        )->field(
            array(
                "savepath","savename","id","name","ext"
            )
        )->find();
        if(empty($old_file_data)) {
            $moveResult=$fileObj->move($this->uploadPath.$uploadPath);
            $isImg    = $moveResult->verifyImg($this->uploadPath. $uploadPath. DS . $moveResult->getSaveName());
            $width    = 0;
            $height   = 0;
            if ($isImg == true) {
                $image  = Image::open($this->uploadPath.$uploadPath. DS . $moveResult->getSaveName());
                $width  = $image->width();
                $height = $image->height();
            }
            $fileData=[
                'name' => $fileInfo["name"], //原始名称
                'savename' =>$moveResult->getFilename(),
                "savepath" => substr($moveResult->getSaveName(), 0, strpos($moveResult->getSaveName(), DS)),
                'ext' => str_replace('.','',strrchr($fileInfo["name"],'.')), //后缀名
                'mime' => $moveResult->getMime(), //
                'size' => $moveResult->getSize(), //大小
                'md5' => $file_md5, //对原始附件md5
                'sha1' => $file_sha1,//对原始附件sha1
                "data_from"=>$extraData["data_from"],
                "uid"=>$extraData["uid"],
            ];
            $attachmentData["file_name"]= basename($fileInfo["name"], "." . $fileData["ext"]);
            $attachmentData["isImg"]=$isImg === true ? 1 : 0;
            $attachmentData["width"]=$width;
            $attachmentData["height"]=$height;

            db()->startTrans();
            $file_id = db("file")->insertGetId($fileData);
            $attachmentData["file_id"]=$file_id;
            $flagB = db("attachment_link")->insert($attachmentData);
            if ($file_id && $flagB) {
                db()->commit();
                array_push($this->fileId, (int)$file_id);
                array_push($this->visit_path, config("VIEW_FILE_PATH") . $upload_config["upload_path"] . "/" . $fileData["savepath"] . "/" . $fileData["savename"]);
                return $file_id;
            } else {
                db()->rollback();
                $errorMsg = ($fileInfo["name"]) . $fileObj->getError();
                array_push($this->errorMsg, $errorMsg);
                $this->errorNum += 1;
                return false;
            }
        }else{
            //验证 attachment_link 是否存在
            $where = [
                'file_id'              => $old_file_data["id"],
                'attachment_model_id'  => $extraData['model_id'],
                'attachment_folder_id' => $extraData['folder_id'],
                'target_id'            => $extraData['target_id'],
            ];
            $attachmentId = db('attachment_link')->where($where)->value('id');
            if ($attachmentId > 0) {
                $errorMsg=($fileInfo["name"])."文件已存在！";
                array_push($this->errorMsg,$errorMsg);
                array_push($this->fileId,(int)$old_file_data["id"]);
                array_push($this->visit_path,config("VIEW_FILE_PATH").$upload_config["upload_path"]."/".$old_file_data["savepath"]."/".$old_file_data["savename"]);
                return $this->fileId;
            }else{
                $isImg    = $fileObj->verifyImg($this->uploadPath. $uploadPath. DS . $old_file_data["savepath"].DS.$old_file_data["savename"]);
                $width    = 0;
                $height   = 0;
                if ($isImg == true) {
                    $image  = Image::open($this->uploadPath. $uploadPath. DS .$old_file_data["savepath"].DS.$old_file_data["savename"]);
                    $width  = $image->width();
                    $height = $image->height();
                }
                $attachmentData["file_id"]=$old_file_data["id"];
                $attachmentData["file_name"]= basename($old_file_data["name"], "." . $old_file_data["ext"]);
                $attachmentData["isImg"]=$isImg === true ? 1 : 0;
                $attachmentData["width"]=$width;
                $attachmentData["height"]=$height;
                db("attachment_link")->insert($attachmentData);
                array_push($this->fileId,(int)$old_file_data["id"] );
                array_push($this->visit_path,config("VIEW_FILE_PATH").$upload_config["upload_path"]."/".$old_file_data["savepath"]."/".$old_file_data["savename"]);
                return $old_file_data["id"];
            }
        }

    }

    /**
     * @param $fileObj
     * @param $upload_config
     * @param $extraData
     * @return array|bool|int
     * 非模型上传 fileID
     */
    private function independentUpload($fileObj,$upload_config,$extraData){
        $uploadPath = $upload_config["upload_path"];
        $fileInfo= $fileObj->getInfo(); //获取附件信息
        $file_sha1= sha1_file($fileInfo['tmp_name']);
        $file_md5 = md5_file($fileInfo['tmp_name']);
        //验证附件是否符合条件
        $validate=$this->validateFile($fileObj,$upload_config);
        if(empty($validate)){
            return $validate;
        }
        $fileData=[
            'name' => $fileInfo["name"], //原始名称
            'savename' =>"", //存入系统后的名称
            'ext' => str_replace('.','',strrchr($fileInfo["name"],'.')), //后缀名
            'mime' => $fileObj->getMime(), //
            'size' => $fileObj->getSize(), //大小
            'md5' => $file_md5, //对原始附件md5
            'sha1' => $file_sha1,//对原始附件sha1
            "data_from"=>$extraData["data_from"],
            "uid"=>$extraData["uid"]
        ];
        //文件已存在
        $old_file_data=db("file")->where(
            array(
                "md5"=>$file_md5,
                "data_from"=>$fileData["data_from"],
                "uid"=>$fileData["uid"]
            )
        )->field(
            array(
                "savepath","savename","id"
            )
        )->find();
        if(empty($old_file_data)){
            $moveResult=$fileObj->move($this->uploadPath. $uploadPath);
            if ($moveResult) {
                $fileData["savepath"] = substr($moveResult->getSaveName(), 0, strpos($moveResult->getSaveName(), DS));
                $fileData["savename"] = $moveResult->getFilename();
                $file_id=db("file")->insert($fileData,false,true);
                array_push($this->fileId,(int)$file_id );
                array_push($this->visit_path,config("VIEW_FILE_PATH").$upload_config["upload_path"]."/".$fileData["savepath"]."/".$fileData["savename"]);
                return $file_id;
            }else{
                $errorMsg=($fileInfo["name"]).$fileObj->getError();
                array_push($this->errorMsg,$errorMsg);
                $this->errorNum+=1;
                return false;
            }
        }else{
            $errorMsg=($fileInfo["name"])."文件已存在！";
            array_push($this->errorMsg,$errorMsg);
            array_push($this->fileId,(int)$old_file_data["id"]);
            array_push($this->visit_path,config("VIEW_FILE_PATH").$upload_config["upload_path"]."/".$old_file_data["savepath"]."/".$old_file_data["savename"]);
            return $this->fileId;
        }
    }

    /**
     * @param $fileObj
     * @param $upload_config
     * @param $extraData
     * @return array|bool|int
     * 特殊数据表上传
     */
    private function specialUpload($fileObj,$upload_config,$extraData){
        $uploadPath = $upload_config["upload_path"];
        $fileInfo= $fileObj->getInfo(); //获取附件信息
        $file_sha1= sha1_file($fileInfo['tmp_name']);
        $file_md5 = md5_file($fileInfo['tmp_name']);

        //验证附件是否符合条件
        $validate=$this->validateFile($fileObj,$upload_config);
        if(empty($validate)){
            return $validate;
        }
        $attachmentData = [
            'file_id' => 0,
            'file_name' => "",
            'folder_id' => $extraData['folder_id'],
            'create_time' => date('Y-m-d H:i:s'),
        ];
        //文件已存在
        $old_file_data=db("file")->where(
            array(
                "md5"=>$file_md5,
                "data_from"=>$extraData["data_from"],
                "uid"=>$extraData["uid"]
            )
        )->field(
            array(
                "savepath","savename","id","name","ext"
            )
        )->find();
        if(empty($old_file_data)) {
            $moveResult=$fileObj->move($this->uploadPath. $uploadPath);
            $fileData=[
                'name' => $fileInfo["name"], //原始名称
                'savename' =>$moveResult->getFilename(),
                "savepath" => substr($moveResult->getSaveName(), 0, strpos($moveResult->getSaveName(), DS)),
                'ext' => str_replace('.','',strrchr($fileInfo["name"],'.')), //后缀名
                'mime' => $moveResult->getMime(), //
                'size' => $moveResult->getSize(), //大小
                'md5' => $file_md5, //对原始附件md5
                'sha1' => $file_sha1,//对原始附件sha1
                "data_from"=>$extraData["data_from"],
                "uid"=>$extraData["uid"],
            ];
            $attachmentData["file_name"]= basename($fileInfo["name"], "." . $fileData["ext"]);

            db()->startTrans();
            $file_id = db("file")->insertGetId($fileData);
            $attachmentData["file_id"]=$file_id;
            $flagB = db($extraData["db_table"])->insert($attachmentData);
            if ($file_id && $flagB) {
                db()->commit();
                array_push($this->fileId, (int)$file_id);
                array_push($this->visit_path, config("VIEW_FILE_PATH") . $upload_config["upload_path"] . "/" . $fileData["savepath"] . "/" . $fileData["savename"]);
                return $file_id;
            } else {
                db()->rollback();
                $errorMsg = ($fileInfo["name"]) . $fileObj->getError();
                array_push($this->errorMsg, $errorMsg);
                $this->errorNum += 1;
                return false;
            }
        }else{
            //验证 attachment_link 是否存在
            $where = [
                'file_id'              => $old_file_data["id"],
                'folder_id' => $extraData['folder_id'],
            ];
            $attachmentId = db($extraData["db_table"])->where($where)->value('id');
            if ($attachmentId > 0) {
                $errorMsg=($fileInfo["name"])."文件已存在！";
                array_push($this->errorMsg,$errorMsg);
                array_push($this->fileId,(int)$old_file_data["id"]);
                array_push($this->visit_path,config("VIEW_FILE_PATH").$upload_config["upload_path"]."/".$old_file_data["savepath"]."/".$old_file_data["savename"]);
                return $this->fileId;
            }else{
                $attachmentData["file_id"]=$old_file_data["id"];
                $attachmentData["file_name"]= basename($old_file_data["name"], "." . $old_file_data["ext"]);
                db($extraData["db_table"])->insert($attachmentData);
                array_push($this->fileId,(int)$old_file_data["id"] );
                array_push($this->visit_path,config("VIEW_FILE_PATH").$upload_config["upload_path"]."/".$old_file_data["savepath"]."/".$old_file_data["savename"]);
                return $old_file_data["id"];
            }
        }
    }



    /**
     * @param $fileObj file对象
     * @param array $upload_config 配置
     * @param array $rule 额外规则
     * @return array
     * 验证附件是否符合要求
     */
    protected function validateFile($fileObj,$upload_config=array(),$rule=[]){
        $fileInfo=$fileObj->getInfo();
        $type       = $upload_config['upload_file_type'];
        $size       = $upload_config['upload_file_size'];
        $info       = $fileObj->check(['size' => $size, 'ext' => $type]);
        if (empty($info)) {
            $errorMsg=($fileInfo["name"]).$fileObj->getError();
            array_push($this->errorMsg,$errorMsg);
            $this->errorNum+=1;
            return false;
        }else{
            return true;
        }
    }










}