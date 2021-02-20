<?php

namespace app\admin\model;

use think\Model;


class SnRecordBak extends Model
{

    

    

    // 表名
    protected $name = 'sn_record_bak';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'time_text',
        'type_text',
        'cardClass_text',
        'ctime_text',
        'status_text',
        'ftime_text'
    ];
    

    
    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2')];
    }

    public function getCardclassList()
    {
        return ['00' => __('Cardclass 00'), '01' => __('Cardclass 01'), '02' => __('Cardclass 02'), '03' => __('Cardclass 03'), '04' => __('Cardclass 04'), '10' => __('Cardclass 10'), '11' => __('Cardclass 11'), '12' => __('Cardclass 12'), '14' => __('Cardclass 14'), '20' => __('Cardclass 20'), '21' => __('Cardclass 21'), '22' => __('Cardclass 22')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }


    public function getTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['time']) ? $data['time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCardclassTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['cardClass']) ? $data['cardClass'] : '');
        $list = $this->getCardclassList();
        return isset($list[$value]) ? $list[$value] : '';
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


    public function getFtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ftime']) ? $data['ftime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setFtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function auser()
    {
        return $this->belongsTo('Auser', 'u_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
