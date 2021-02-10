<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\model\Order as odr;
use think\Db;
use think\Exception;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{
    
    /**
     * Order模型对象
     * @var \app\admin\model\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Order;
        $this->view->assign("statusList", $this->model->getStatusList());
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['auser','agoods'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['auser','agoods'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                
                $row->getRelation('auser')->visible(['mobile','nickName','avatar']);
				$row->getRelation('agoods')->visible(['name','image','price','factory','type']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }


    //完成订单
    /*
     * 购买数量30台 直接升级为 V5等级
     * 200->v6
     * 500->V7
     * 1000->V8
     *
     * */
    public function done()
    {
        $id = $this->request->param('ids');
        $orderinfo = odr::get($id);
        Db::startTrans();
        try{
            if ($orderinfo->num >= 30 && $orderinfo->num < 200){
                //直接升级为 v5
                $this->ups('V5',$orderinfo->u_id);
            }elseif($orderinfo->num >= 200 && $orderinfo->num < 500){
                //直接升级为 v6
                $this->ups('V6',$orderinfo->u_id);
            }elseif($orderinfo->num >= 500 && $orderinfo->num < 1000){
                //直接升级为 v7
                $this->ups('V7',$orderinfo->u_id);
            }elseif($orderinfo->num >= 1000){
                //直接升级为 v8
                $this->ups('V8',$orderinfo->u_id);
            }
            $orderinfo->status = '5';//直接修改状态为已完成就行
            $orderinfo->save();
            Db::commit();
            $this->success('订单已成功');

        }catch(Exception $exception){
            Db::rollback();
            $this->error('失败'.$exception->getMessage());
        }

    }

    /*
     * 修改等级
     * @param $level_name string  等级名字
     * @param $uid int 用户id
     * */
    private function ups($level_name,$uid)
    {
        $level_id = Db::name('level')->where('name',$level_name)->value('id');
        $ret = Db::name('auser')->where('id',$uid)->setField('level_id',$level_id);
        if ($ret){
            return 1;
        }else{
            return 0;
        }
    }

}
