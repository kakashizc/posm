define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'auser/index' + location.search,
                    add_url: 'auser/add',
                    edit_url: 'auser/edit',
                    del_url: 'auser/del',
                    multi_url: 'auser/multi',
                    table: 'auser',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'openid', title: __('Openid')},
                        {field: 'level.name', title: '等级'},
                        {field: 'nickName', title: __('Nickname')},
                        {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'money', title: __('Money'), operate:'BETWEEN'},
                        {field: 'total', title: __('Total'), operate:'BETWEEN'},
                        {field: 'code', title: __('Code')},
                        {field: 'pid', title: __('Pid')},
                        {field: 'ctime', title: __('Ctime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'server_num', title: __('Server_num')},
                        {field: 'qrcode', title: __('Qrcode')},
                        {field: 'indent_face_image', title: __('Indent_face_image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'indent_back_image', title: __('Indent_back_image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'indent_no', title: __('Indent_no')},
                        {field: 'indent_name', title: __('Indent_name')},
                        {field: 'recive_name', title: __('Recive_name')},
                        {field: 'recive_mobile', title: __('Recive_mobile')},
                        {field: 'recive_city', title: __('Recive_city')},
                        {field: 'recive_address', title: __('Recive_address')},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3')}, formatter: Table.api.formatter.status},
                        {field: 'operate', title: __('Operate'),
                            buttons:[
                                {
                                    name: 'detail',
                                    text: '划拨机具',
                                    title: '划拨机具',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-address-book-o',
                                    url: 'Auser/mac'
                                },
                                {
                                    name: 'detail',
                                    text: '通过',
                                    title: '审核通过',
                                    classname: 'btn btn-xs btn-primary btn-ajax',
                                    icon: 'fa fa-address-book-o',
                                    url: 'auser/pass',
                                    confirm:'确认通过?',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger('click')
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.status == '1'){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '拒绝',
                                    title: '审核拒绝通过',
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    icon: 'fa fa-address-book-o',
                                    url: 'auser/negative',
                                    confirm:'确认拒绝通过?',
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        $(".btn-refresh").trigger('click')
                                        return false;
                                    },
                                    visible: function (row) {
                                        if (row.status == '1'){
                                            return true;
                                        }else{
                                            return false;
                                        }
                                    }
                                },
                            ],
                            table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});