<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\model\Agoods;
use app\admin\model\AgoodsSn;
use think\Db;
use think\Exception;

/**
 * 用户管理
 *
 * @icon fa fa-circle-o
 */
class Auser extends Backend
{
    
    /**
     * Auser模型对象
     * @var \app\admin\model\Auser
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Auser;
        $this->view->assign("statusList", $this->model->getStatusList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /*
     * 划拨机具
     *
     * */
    public function mac()
    {
        $id = $this->request->param('ids');
        //查找所有机具
        $goods = Agoods::all(function($list){
            $list->field('id,name,price,factory,type')->where('status','1');
        })->each(function ($item){
            if ($item['type'] == '1'){
                $item['type'] = '小pos';
            }else{
                $item['type'] = '大pos';
            }
            return $item;
        });
        return $this->fetch('mac',['id'=>$id,'goods'=>$goods]);
    }

    /*
     * 执行划拨
     *
     * */
    public function add_mac()
    {
        $start = $this->request->param('start');//开始编号
        $end = $this->request->param('end');//结束编号
        $id = $this->request->param('id');//用户id
        $good_id = $this->request->param('good_id');//机具id
        if($start < $end){
            $count = bcsub($end,$start);
        }else{
            $count = bcsub($start,$end);
            $mid = $start;
            $start = $end;
            $end = $mid;
        }
        //插入sn表,并绑定u_id
        $time = time();
        for ($i=0;$i<=$count;$i++){
            if ($i == 0) {
                $data[$i]['sn'] = $start;
            }elseif ($i == $count){
                $data[$i]['sn'] = $end;
            }else{
                $data[$i]['sn'] = bcadd( $start,"$i");
            }
            //查询是否已存在的sn号
            $is = AgoodsSn::get(['sn'=>$data[$i]['sn']]);
            if ($is){
                $this->error($data[$i]['sn'].'---此sn号已存在, 请核实后再划拨');
            }
            $data[$i]['sn'];
            $data[$i]['good_id'] = $good_id;
            $data[$i]['u_id'] = $id;
            $data[$i]['ctime'] = $time;
        }
        Db::startTrans();
        try{
            $res = Db::name('agoods_sn')->insertAll($data);
            if ($res){
                //插入一条划拨机具的记录
                $rec['op_id'] = 0;
                $rec['time'] = time();
                $rec['start'] = $start;
                $rec['end'] = $end;
                $rec['u_id'] = $id;
                $rec['no'] = start_end_tostr($start,$end);
                $rec['goods_id'] = $good_id;
                Db::name('agoods_sn_record')->insert($rec);
                Db::commit();
                $this->success('划拨成功');
            }
        }catch(Exception $exception){
            $msg = $exception->getMessage();
            Db::rollback();
            $this->error("划拨失败,$msg");
        }

    }

}
