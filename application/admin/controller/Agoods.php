<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\Db;
/**
 * 机具管理
 *
 * @icon fa fa-circle-o
 */
class Agoods extends Backend
{
    
    /**
     * Agoods模型对象
     * @var \app\admin\model\Agoods
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Agoods;
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("statusList", $this->model->getStatusList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /*
     * 导入sn
     * */
    public function imp()
    {
        $id = $this->request->param('ids');
        return $this->fetch('addsn',['id'=>$id]);
    }

    public function add_sn()
    {
        $id = $this->request->param('id');
        $this->imports($id);
    }
    /*
    * phpspreedsheet
    * 可以是xlsx 或者 xls 其中一个类型
    * */
    private function imports($id)
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
        if ($fileExtendName != 'xls') {
            $this->error('必须为excel表格，且必须为xls格式！');
            exit();
        }

        if (is_uploaded_file($_FILES['myfile']['tmp_name'])) {
            // 有Xls和Xlsx格式两种
            $objReader = IOFactory::createReader('Xls');

            $filename = $_FILES['myfile']['tmp_name'];
            $objPHPExcel = $objReader->load($filename);  //$filename可以是上传的表格，或者是指定的表格
            $sheet = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
            $highestRow = $sheet->getHighestRow();       // 取得总行数

            //定义$usersExits，循环表格的时候，找出已存在的用户。
            $usersExits = [];
            //循环读取excel表格，整合成数组。如果是不指定key的二维，就用$data[i][j]表示。
            for ($j = 2; $j <= $highestRow; $j++) {
                $data[$j - 2] = [
                    'sn' => $objPHPExcel->getActiveSheet()->getCell("A" . $j)->getValue(),
                    'good_id' => $id
                ];
                //看下用户名是否存在。将存在的用户名保存在数组里。
                $userExist = DB::name('agoods_sn')
                    ->where('sn', $data[$j - 2]['sn'])
                    ->find();
                if ($userExist) {
                    array_push($usersExits, $data[$j - 2]['sn']);
                }
            }

            //如果有已存在的用户名，就不插入数据库了。
            if ($usersExits != []) {
                //把数组变成字符串，向前端输出。
                $c = implode(" / ", $usersExits);
                $this->error('sn已存在:' . $c);
                exit();
            }

            $res = DB::name('agoods_sn')->insertAll($data);
            if ($res) {
                $this->success('上传成功！');
            }
        }else{
            $this->error('失败');
        }
    }

}
