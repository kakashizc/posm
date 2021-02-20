define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'sn_bind/index' + location.search,
                    add_url: 'sn_bind/add',
                    edit_url: 'sn_bind/edit',
                    del_url: 'sn_bind/del',
                    multi_url: 'sn_bind/multi',
                    table: 'sn_bind',
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
                        {field: 'agentNo', title: __('Agentno')},
                        {field: 'memNo', title: __('Memno')},
                        {field: 'termNo', title: __('Termno')},
                        {field: 'snNo', title: __('Snno')},
                        {field: 'brandSn', title: __('Brandsn')},
                        {field: 'activityType', title: __('Activitytype')},
                        {field: 'bindDate', title: __('Binddate')},
                        {field: 'bindTime', title: __('Bindtime')},
                        {field: 'memName', title: __('Memname')},
                        {field: 'memPhone', title: __('Memphone')},
                        {field: 'time', title: __('Time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'agentId', title: __('Agentid')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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