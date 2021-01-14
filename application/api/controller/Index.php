<?php

namespace app\api\controller;

use app\common\controller\Api;
use PhpOffice\PhpSpreadsheet\IOFactory;
/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    private $_rsa;
    public function _initialize()
    {
        parent::_initialize();
        $this->_rsa = new Rsa();
    }

    /**
     * 首页
     *
     */
    public function index()
    {

        $data['name'] = 'Tom';
        $data['age']  = '20';
//        $privEncrypt = $this->_rsa->privEncrypt(json_encode($data));
//        echo '私钥加密:'.$privEncrypt.'<br>';

//        $publicDecrypt = $this->_rsa->publicDecrypt($privEncrypt);
//        echo '公钥解密:'.$publicDecrypt.'<br>';

//        $publicEncrypt = $this->_rsa->publicEncrypt(json_encode($data));
//        echo '公钥加密:'.$publicEncrypt.'<br>';
        //$publicEncrypt = "KwThwcb97W23MSWZiTjkjhS1wqjLAVbiCfOEFOt2Bs5krZCZCU/mpVwz91UBMl8wZ3isdX/I0/pmdbpIqBHLOQ==";
        $publicEncrypt = "q7Qs/T2ksp3I6aXSsjC75u8HXhVZSUWrD%2BooPQLcqhFz8uCmOk5G9CM/HvEzBecUHg3jKHNCrw7OelyAJeykmw==";
        $publicEncrypt = str_replace('%2B','+',$publicEncrypt);
        $privDecrypt = $this->_rsa->privDecrypt($publicEncrypt);
        echo '私钥解密:'.$privDecrypt.'<br>';
    }

    /*
     * 加解密测试
     * */
    public function test()
    {
        $data = $this->request->param('pass');
        $publicDecrypt = $this->_rsa->publicDecrypt($data);
        echo '公钥解密:'.$publicDecrypt.'<br>';
    }

    /*
     * phpspreedsheet
     * 可以是xlsx 或者 xls 其中一个类型
     * */
    public function importAdmin()
    {
        $file = $this->request->file('myfile');
        if (!$file) $this->error('无数据');
        //获取表格的大小，限制上传表格的大小5M
        $file_size = $_FILES['myfile']['size'];
        if ($file_size > 5 * 1024 * 1024) {
            $this->error('文件大小不能超过5M');
            exit();
        }

        //限制上传表格类型
        $fileExtendName = substr(strrchr($_FILES['myfile']["name"], '.'), 1);
        //application/vnd.ms-excel  为xls文件类型
        if ($fileExtendName != 'xlsx') {
            $this->error('必须为excel表格，且必须为xls格式！');
            exit();
        }

        if (is_uploaded_file($_FILES['myfile']['tmp_name'])) {
            // 有Xls和Xlsx格式两种
            $objReader = IOFactory::createReader('Xlsx');

            $filename = $_FILES['myfile']['tmp_name'];
            $objPHPExcel = $objReader->load($filename);  //$filename可以是上传的表格，或者是指定的表格
            $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
            $highestRow = $sheet->getHighestRow();       // 取得总行数
            // $highestColumn = $sheet->getHighestColumn();   // 取得总列数

            //定义$usersExits，循环表格的时候，找出已存在的用户。
            $usersExits = [];
            //循环读取excel表格，整合成数组。如果是不指定key的二维，就用$data[i][j]表示。
            for ($j = 2; $j <= $highestRow; $j++) {
                $data[$j - 2] = [
                    'name' => $objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue(),
                    'pass' => $objPHPExcel->getActiveSheet()->getCell("B" . $j)->getValue(),
                    'ctime' => time()
                ];
                //看下用户名是否存在。将存在的用户名保存在数组里。
                $userExist = db('aaa')->where('name', $data[$j - 2]['name'])->find();
                if ($userExist) {
                    array_push($usersExits, $data[$j - 2]['name']);
                }
            }

            //如果有已存在的用户名，就不插入数据库了。
            if ($usersExits != []) {
                //把数组变成字符串，向前端输出。
                $c = implode(" / ", $usersExits);
                $this->error('Excel中以下用户名已存在:' . $c);
                exit();
            }

            $res = db('aaa')->insertAll($data);
            if ($res) {
                $this->success('上传成功！', '', 0);
            }
        }else{
            $this->error('失败','','1');
        }
    }
}
