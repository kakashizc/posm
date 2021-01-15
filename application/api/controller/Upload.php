<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\controller\Image;
use think\Db;
header('Access-Control-Allow-Origin:*');

class Upload extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function up()
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        $data = [];
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size' => 1567800, 'ext' => 'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            $date = date('Ymd');
            $path = $info->getSaveName();
            $ext = $info->getExtension();
            $strs = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
            $name = substr(str_shuffle($strs),mt_rand(0,strlen($strs)-11),10);
            if(!file_exists(ROOT_PATH . 'public' . DS . 'uploads/thumb/'.$date)){
                mkdir(ROOT_PATH . 'public' . DS . 'uploads/thumb/'.$date);
            }
            $str = $date.'/'. $name.'.'.$ext;
            copy('uploads/'.$path,ROOT_PATH . 'public' . '/' . 'uploads/thumb/'.$str);
            $path = str_replace('\\', '/', $path);
            $compress = new Image('uploads/thumb/' . $str, 0.5);//压缩图片
            $compress->compressImg('uploads/thumb/' .$str);//压缩图片
            $data['all_image'] = 'uploads/'.$path;
            $data['thumb_image'] = 'uploads/thumb/'.$str;
            $id = Db::name('images')->insertGetId($data);
            $return = [
                'image_id'=>$id,
                'all_image' => '/'.$data['all_image'],//原图
                'thumb_image' => '/'.$data['thumb_image']//缩略图
            ];
            // 成功上传后 获取上传信息
            $this->success('成功', $return, '0');// 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
        } else {
            // 上传失败获取错误信息
            $this->success($file->getError(),'','1');
        }
    }
    
    /*
     * 上传图片 内部使用
     * @param $file 图片文件对象
     * */
    public function ups($file)
    {
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size' => 156780, 'ext' => 'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            $path = $info->getSaveName();
            return $path;
        } else {
            $this->success($file->getError(),'','1');
        }
    }
    /*
     * 师傅端认证,上传各种图片走这个接口
     * */
    public function indent_up()
    {
        // 获取表单上传文件 例如上传了001.jpg
        $type = $this->request->param('types');
        // echo '<pre>';print_r($type);
        $file = $this->request->file('file');
        // echo '<pre>';print_r($file);exit;
        $data = [];
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size' => 1567800, 'ext' => 'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            $date = date('Ymd');
            $path = $info->getSaveName();
            $ext = $info->getExtension();
            $strs = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
            $name = substr(str_shuffle($strs),mt_rand(0,strlen($strs)-11),10);
            if(!file_exists(ROOT_PATH . 'public' . DS . 'uploads/thumb/'.$date)){
                mkdir(ROOT_PATH . 'public' . DS . 'uploads/thumb/'.$date);
            }
            $str = $date.'/'. $name.'.'.$ext;
            copy('uploads/'.$path,ROOT_PATH . 'public' . '/' . 'uploads/thumb/'.$str);
            $path = str_replace('\\', '/', $path);
            $compress = new Image('uploads/thumb/' . $str, 0.5);//压缩图片
            $compress->compressImg('uploads/thumb/' .$str);//压缩图片
            $data['all_image'] = 'uploads/'.$path;
            $data['thumb_image'] = 'uploads/thumb/'.$str;
            $id = Db::name('images')->insertGetId($data);
            $return = [
                'image_id' => $id,
                'all_image' => '/'.$data['all_image'],//原图
                'thumb_image' => '/'.$data['thumb_image'],//缩略图
                'type' => $type
            ];
            // 成功上传后 获取上传信息
            $this->success('成功', $return, '0');// 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
        } else {
            // 上传失败获取错误信息
            $this->success($file->getError(),'','1');
        }
    }
    
    /*
     * 图片加水印
     *
     *  参数说明：
        $imgSrc：目标图片，可带相对目录地址
        $markImg：水印图片，可带相对目录地址，支持PNG和GIF两种格式，如水印图片在执行文件mark目录下，可写成：mark/mark.gif
        $markText：给图片添加的水印文字
        $TextColor：水印文字的字体颜色
        $markPos：图片水印添加的位置，取值范围：0~9
        0：随机位置，在1~8之间随机选取一个位置
        1：顶部居左 2：顶部居中 3：顶部居右 4：左边居中
        5：图片中心 6：右边居中 7：底部居左 8：底部居中 9：底部居右
        $fontType：具体的字体库，可带相对目录地址
        $markType：图片添加水印的方式，img代表以图片方式，text代表以文字方式添加水印
     * */
    public function setWater($imgSrc,$markImg,$markText,$TextColor,$markPos,$fontType,$markType)
    {
        $srcInfo = getimagesize($imgSrc);
        $markInfo = getimagesize($markImg);
        if($srcInfo[0] < $markInfo[0]  || $srcInfo[1] < $markInfo[1]){
            $this->error('图片尺寸小于水印尺寸,请重新上传大图片');
        }
        $srcImg_w  = $srcInfo[0];
        $srcImg_h  = $srcInfo[1];
        switch ($srcInfo[2])
        {
            case 1:
                $srcim =imagecreatefromgif($imgSrc);
                break;
            case 2:
                $srcim =imagecreatefromjpeg($imgSrc);
                break;
            case 3:
                $srcim =imagecreatefrompng($imgSrc);
                break;
            default:
                die("不支持的图片文件类型");
                exit;
        }

        if(!strcmp($markType,"img"))
        {
            if(!file_exists($markImg) || empty($markImg))
            {
                return;
            }
            $markImgInfo = @getimagesize($markImg);
            $markImg_w  = $markImgInfo[0];
            $markImg_h  = $markImgInfo[1];
            if($srcImg_w < $markImg_w || $srcImg_h < $markImg_h)
            {
                return;
            }

            switch ($markImgInfo[2])
            {
                case 1:
                    $markim =imagecreatefromgif($markImg);
                    break;
                case 2:
                    $markim =imagecreatefromjpeg($markImg);
                    break;
                case 3:
                    $markim =imagecreatefrompng($markImg);
                    break;
                default:
                    die("不支持的水印图片文件类型");
                    exit;
            }
            $logow = $markImg_w;
            $logoh = $markImg_h;
        }

        if(!strcmp($markType,"text"))
        {
            $fontSize = 16;
            if(!empty($markText))
            {
                if(!file_exists($fontType))
                {
                    return;
                }
            }else {
                return;
            }
            $box = @imagettfbbox($fontSize, 0, $fontType,$markText);
            $logow = max($box[2], $box[4]) - min($box[0], $box[6]);
            $logoh = max($box[1], $box[3]) - min($box[5], $box[7]);
        }
        if($markPos == 0)
        {
            $markPos = rand(1, 9);
        }
        switch($markPos)
        {
            case 1:
                $x = +5;

                $y = +5;

                break;

            case 2:

                $x = ($srcImg_w - $logow) / 2;

                $y = +5;

                break;

            case 3:

                $x = $srcImg_w - $logow - 5;

                $y = +15;

                break;

            case 4:

                $x = +5;

                $y = ($srcImg_h - $logoh) / 2;

                break;

            case 5:

                $x = ($srcImg_w - $logow) / 2;

                $y = ($srcImg_h - $logoh) / 2;

                break;

            case 6:

                $x = $srcImg_w - $logow - 5;

                $y = ($srcImg_h - $logoh) / 2;

                break;

            case 7:

                $x = +5;

                $y = $srcImg_h - $logoh - 5;

                break;

            case 8:

                $x = ($srcImg_w - $logow) / 2;

                $y = $srcImg_h - $logoh - 5;

                break;

            case 9:

                $x = $srcImg_w - $logow - 5;

                $y = $srcImg_h - $logoh -5;

                break;
            default:
                die("此位置不支持");
                exit;
        }

        $dst_img = @imagecreatetruecolor($srcImg_w, $srcImg_h);
        imagecopy ( $dst_img, $srcim, 0, 0, 0, 0, $srcImg_w, $srcImg_h);
        if(!strcmp($markType,"img"))
        {
            imagecopy($dst_img, $markim, $x, $y, 0, 0, $logow, $logoh);
            imagedestroy($markim);
        }

        if(!strcmp($markType,"text"))
        {
            $rgb = explode(',', $TextColor);
            $color = imagecolorallocate($dst_img, $rgb[0], $rgb[1], $rgb[2]);

            imagettftext($dst_img, $fontSize, 0, $x, $y, $color, $fontType,$markText);
        }
        switch ($srcInfo[2])
        {
            case 1:
                imagegif($dst_img, $imgSrc);
                break;
            case 2:
                imagejpeg($dst_img, $imgSrc);
                break;
            case 3:
                imagepng($dst_img, $imgSrc);
                break;
            default:
                die("不支持的水印图片文件类型");
                exit;
        }
        imagedestroy($dst_img);
        imagedestroy($srcim);
    }
    
}