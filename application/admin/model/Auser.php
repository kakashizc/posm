<?php

namespace app\admin\model;

use think\Db;
use think\Model;


class Auser extends Model
{

    

    

    // 表名
    protected $name = 'auser';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'ctime_text',
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3')];
    }


    public function getCtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ctime']) ? $data['ctime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setCtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function level()
    {
        return $this->belongsTo('Level', 'level_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /*
     * 查找当前用户交易额
     * @param int $uid   用户id
     * @param int $type  类型 1-日交易额 2-月交易额
     * */
    public static function trade($uid,$type)
    {
        switch ($type){
            case '1'://查询日交易额
                $stime = strtotime(date("Y-m-d"),time()); //今天开始时间戳
                $etime = $stime + 3600*24;//今天结束时间戳
                $money = Db::name('sn_record')
                    ->whereTime('time',[$stime,$etime])
                    ->where('u_id',$uid)
                    ->sum('money');
                break;
            case '2'://查询月交易额
                $this_month = date('Y-m',time());
                $money = Db::name('sn_record')
                    ->where('date',$this_month)
                    ->where('u_id',$uid)
                    ->sum('money');
                break;
        }
        return $money;
    }
}
