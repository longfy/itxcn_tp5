<?php
namespace app\index\logic;

use Think\Db;

/**
 * 上传文件处理
 * 2019/05/30
 */
class UploadFileLogic extends CommonLogic
{
    // 验证文件
    public function uploadFile($param)
    {
        $return = array(
            'success'    => 0,
            'successMsg' => '',
            'error'      => 0,
            'errorMsg'   => '',
        );
        $data['model_id']  = $param['model_id'];
        $data['folder_id'] = $param['folder_id'];
        $data['target_id'] = $param['target_id'];

        foreach ($param['files'] as $v) {
            if (is_array($v)) {
                foreach ($v as $vv) {
                    $result = self::upload($vv, $data);
                    if ($result['status'] == true) {
                        $return['success']++;
                        $return['successMsg'] .= $result['fileName'] . $result['msg'] . ",";
                    } else {
                        $return['error']++;
                        $return['errorMsg'] .= $result['fileName'] . $result['msg'] . ",";
                    }
                    @unlink($result['filePath']);
                }
            } else {
                $result = self::upload($v, $data);
                if ($result['status'] == true) {
                    $return['success']++;
                    $return['successMsg'] .= $result['fileName'] . $result['msg'] . ",";
                } else {
                    $return['error']++;
                    $return['errorMsg'] .= $result['fileName'] . $result['msg'] . ",";
                }
                @unlink($result['filePath']);
            }
        }
        $returnMsg = json_encode(msgReturn('未有文件上传！'));
        if ($return['error'] > 0 && $return['success'] == 0) {
            $msg       = $return['error'] . "个文件上传失败.详情: " . rtrim($return['errorMsg'], ',');
            $returnMsg = json_encode(msgReturn($msg));
        } else if ($return['error'] > 0 && $return['success'] > 0) {
            $msg       = $return['error'] . "个文件上传失败.详情: " . rtrim($return['errorMsg'], ',');
            $returnMsg = json_encode(msgReturn($msg, true));
        } else if ($return['error'] == 0 && $return['success'] > 0) {
            $returnMsg = json_encode(msgReturn('上传成功！', true));
        }
        return $returnMsg;
    }

    // 上传文件
    public function upload($file, $data = array())
    {
        $uploadPath = config('upload_path.file_path');
        $type       = config('upload_file_type');
        $size       = config('upload_file_size');
        $fileName   = $file->getInfo()['name'];
        $info       = $file->validate(['size' => $size, 'ext' => $type])->move(ROOT_PATH . $uploadPath);
        if ($info) {
            $savepath = substr($info->getSaveName(), 0, strpos($info->getSaveName(), DS));
            $filePath = ROOT_PATH . $uploadPath . DS . $info->getSaveName();
            $isImg    = $file->verifyImg();
            $width    = 0;
            $height   = 0;
            $path     = false;
            if ($isImg == true) {
                $image  = \think\Image::open($filePath);
                $width  = $image->width();
                $height = $image->height();
            }
            // 成功上传后 获取上传信息
            $fileData = [
                'name'        => $fileName,
                'savename'    => $info->getFilename(),
                'savepath'    => $savepath,
                'ext'         => $info->getExtension(),
                'mime'        => $info->getMime(),
                'size'        => $info->getSize(),
                'md5'         => $info->md5(),
                'sha1'        => $info->sha1(),
                'create_time' => date('Y-m-d H:i:s'),
            ];
            Db::startTrans();
            $fileId = Db::table('file')->where("md5 = '" . $fileData['md5'] . "'")->value('id');
            if ($fileId > 0) {
                $path  = true;
                $flagA = true;
            } else {
                $flagA  = Db::table('file')->insertGetId($fileData);
                $fileId = $flagA;
            }
            $where = [
                'file_id'              => $fileId,
                'attachment_model_id'  => $data['model_id'],
                'attachment_folder_id' => $data['folder_id'],
                'target_id'            => $data['target_id'],
                'delete_time'          => '0000-00-00 00:00:00',
            ];
            $attachmentId = Db::table('attachment_link')->where($where)->value('id');
            if ($attachmentId > 0) {
                Db::rollback();
                return ['status' => false, 'msg' => '文件已上传！', 'fileName' => $fileName, 'filePath' => $filePath];
            }
            $modelType      = Db::table('attachment_model')->where('id = ' . $data['model_id'])->value('type');
            $attachmentData = [
                'file_id'              => $fileId,
                'file_name'            => $fileName,
                'size'                 => $fileData['size'],
                'create_time'          => date('Y-m-d H:i:s'),
                'attachment_model_id'  => $data['model_id'],
                'attachment_folder_id' => $data['folder_id'],
                'target_id'            => $data['target_id'],
                'type_id'              => $modelType,
                'project_id'           => $this->getProjectId(),
                'width'                => $width,
                'height'               => $height,
                'isImg'                => $isImg === true ? 1 : 0,
                'handler_uid'          => $this->getUid(),
            ];
            $flagB = Db::table('attachment_link')->insert($attachmentData);
            if ($flagA && $flagB) {
                Db::commit();
                if ($path == false) {
                    $filePath = '';
                }
                return ['status' => true, 'msg' => '上传成功！', 'fileName' => $fileName, 'filePath' => $filePath];
            } else {
                Db::rollback();
                return ['status' => false, 'msg' => '上传失败！', 'fileName' => $fileName, 'filePath' => $filePath];
            }
        } else {
            // 上传失败获取错误信息
            return ['status' => false, 'msg' => $file->getError(), 'fileName' => $fileName];
        }
    }

    /**
     * 文件重命名
     * @author Ultraman/2018-08-08
     */
    public function rename($param)
    {
        db()->startTrans();
        $update = db('attachment_link')->where(array('id' => $param['id']))->update(array('file_name' => $param['name']));
        if (!$update) {
            db()->rollback();
            return getReturn(3, false);
        }
        $fileId = db('attachment_link')->where(array('id' => $param['id']))->value('file_id');
        $update = db('file')->where(array('id' => $fileId))->update(array('name' => $param['name']));
        if ($update) {
            db()->commit();
            return getReturn(3, true);
        } else {
            db()->rollback();
            return getReturn(3, false);
        }
    }

    /**
     * 高拍仪图片上传
     * @author Ultraman/2018-08-08
     */
    public function uploadBase64($param)
    {
        $images   = $param['images'];
        $modelId  = $param['model_id'];
        $folderId = $param['folder_id'];
        $targetId = $param['target_id'];
        db()->startTrans();
        foreach (json_decode($images) as $key => $value) {
            $saveName = md5(microtime(true));
            $savePath = date('Ymd');
            $path     = ROOT_PATH . config('upload_path.file_path') . DS . $savePath;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
            $tempFile = $path . DS . $saveName;
            @file_put_contents($tempFile, base64_decode($value->base64));
            $image = \think\Image::open($tempFile);
            $data  = array(
                'name'        => $value->name,
                'savename'    => $saveName,
                'savepath'    => $savePath,
                'ext'         => $image->type(),
                'mime'        => $image->mime(),
                'size'        => $image->size(),
                'md5'         => md5_file($tempFile),
                'sha1'        => sha1_file($tempFile),
                'create_time' => date('Y-m-d H:i:s'),
            );
            $fileId = db('file')->where(array('md5' => $data['md5']))->value('id');
            if (!$fileId) {
                $fileId = db('file')->insertGetId($data);
                if (!$fileId) {
                    db()->rollback();
                    return getReturn(3, false);
                }
            }
            $where = array(
                'file_id'              => $fileId,
                'attachment_model_id'  => $modelId,
                'attachment_folder_id' => $folderId,
                'target_id'            => $targetId,
            );
            $attachmentId = db('attachment_link')->where($where)->value('id');
            if (!$attachmentId) {
                $modelType = db('attachment_model')->where(array('id' => $modelId))->value('type');
                $data      = array(
                    'file_id'              => $fileId,
                    'file_name'            => $value->name,
                    'size'                 => $data['size'],
                    'create_time'          => date('Y-m-d H:i:s'),
                    'attachment_model_id'  => $modelId,
                    'attachment_folder_id' => $folderId,
                    'target_id'            => $targetId,
                    'type_id'              => $modelType,
                    'project_id'           => $this->getProjectId(),
                    'width'                => $image->width(),
                    'height'               => $image->height(),
                    'isImg'                => true,
                    'uid'                  => $this->getUid(),
                );
                $insert = db('attachment_link')->insert($data);
                if (!$insert) {
                    db()->rollback();
                    return getReturn(3, false);
                }
            }
        }
        db()->commit();
        return getReturn(3, true);
    }

}
