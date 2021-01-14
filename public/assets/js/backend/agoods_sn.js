define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'agoods_sn/index' + location.search,
                    add_url: 'agoods_sn/add',
                    edit_url: 'agoods_sn/edit',
                    del_url: 'agoods_sn/del',
                    multi_url: 'agoods_sn/multi',
                    table: 'agoods_sn',
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
                        {field: 'sn', title: __('Sn')},
                        {field: 'good_id', title: __('Good_id')},
                        {field: 'agoods.name', title: __('Agoods.name')},
                        {field: 'agoods.image', title: __('Agoods.image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'agoods.price', title: __('Agoods.price'), operate:'BETWEEN'},
                        {field: 'agoods.factory', title: __('Agoods.factory')},
                        {field: 'agoods.type', title: __('Agoods.type')},
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