/**
 * @link https://layui.dev/docs/2
 */
const admin = {
    /**
     * 初始化配置
     */
    config: {
        debug: true,
        url: {
            imageSave: '/api/m/tao/user.file/save',
            imageList: '/m/tao/user.file/index',
        },
        ajax: {
            headers: function () {
                if (!admin.util.isEmpty(window.CONFIG) && !admin.util.isEmpty(window.CONFIG.CSRF_TOKEN)) {
                    return {'X-CSRF-TOKEN': window.CONFIG.CSRF_TOKEN}
                }
                return {};
            },
            refreshHeaders: function (method = 'get') {
                if (['post', 'put', 'delete'].includes(method)) {
                    console.log('try to refresh csrf-token');
                    // @todo 刷新 csrf-token
                }
            }
        }
    },
    debug: function () {
        return admin.config.debug || false;
    },
    /**
     * 覆盖 admin 默认配置
     * @param option {Object}
     */
    assign: function (option) {
        Object.assign(admin.config, option)
    },
    /**
     * 工具类
     */
    util: {
        isEmpty: function (data) {
            if (data === null || data === undefined) {
                return true;
            }
            switch (typeof data) {
                case "undefined":
                    return true;
                case "number":
                    return data === 0;
                case "string":
                    data = data.trim();
                    return data === '0' ||
                        data === 'null' ||
                        data === '' ||
                        data === 'undefined' ||
                        data === '0001-01-01T00:00:00Z' ||
                        data === '0001-01-01 00:00:00';
                case "object":
                    const r = JSON.stringify(data);
                    return r === '{}' || r === '[]'
                case "boolean":
                    return !data;
                default:
                    return !!data;
            }
        },
        /**
         * 判断是否为手机
         * @returns {boolean}
         * @link https://layui.dev/docs/2/base.html#device
         */
        checkMobile: function () {
            return layui.device('mobile');
        },
        /**
         * 拼接成 URL 请求参数
         * @link https://layui.dev/docs/2/base.html#url
         * @param dict {Object}
         * @returns {string}
         */
        concatQuery: function (dict) {
            var d = [];
            for (var key in dict) {
                d.push(key + '=' + dict[key]);
            }
            return d.join('&');
        },
        /**
         * @param {Object} binds 绑定操作 {name:function(){}}
         * @link https://layui.dev/docs/2/util/#on
         */
        layOn: function (binds) {
            layui.util.on('lay-on', binds);
        },
    },
    layer: {
        image: function (src, title = '圖片展示') {
            layer.photos({
                photos: {
                    title, start: 0, data: [{src}]
                }
            })
        }
    },
    /**
     * 消息提示
     * @link https://layui.dev/docs/2/layer/#options.callback
     */
    msg: {
        /**
         * 成功提示
         * @public
         * @param msg {string} 提示信息
         * @param {function} [callback]
         */
        success: function (msg, callback) {
            return layui.layer.msg(msg, {
                icon: 1,
                scrollbar: false,
                time: 2000,
                shadeClose: true
            }, this._createCallback_(callback));
        },
        /**
         * 失败提示
         * @public
         * @param msg {string}
         * @param {function} [callback]
         */
        error: function (msg, callback) {
            return layui.layer.msg(msg, {
                icon: 2,
                scrollbar: false,
                time: 3000,
                shadeClose: true
            }, this._createCallback_(callback))
        },
        /**
         * 警告消息框
         * @public
         * @link https://layui.dev/docs/2/layer/#options
         * @param msg {string}
         * @param {function} [callback]
         * @param {{title:string}} [options]
         */
        alert: function (msg, callback, options = {}) {
            return layui.layer.alert(msg, Object.assign({
                scrollbar: false
            }, options), function (index) {
                layui.layer.close(index);
                if (typeof callback == "function") {
                    callback();
                }
            });
        },
        /**
         * 询问框
         * @public
         * @param msg {string}
         * @param {function} [ok] 确认操作回调
         * @param {function} [no] 取消操作回调
         */
        confirm: function (msg, ok, no) {
            return layui.layer.confirm(msg, {title: '操作确认', btn: ['确认', '取消']}, function (index) {
                if (typeof ok === 'function') {
                    ok(index);
                }
                admin.msg.close(index);
            }, function (index) {
                if (typeof no === 'function') {
                    no(index);
                }
                admin.msg.close(index)
            });
        },
        /**
         * 消息提示框(贴士层)
         * @param msg {string}
         * @param time {number} 秒数，默认为 3
         * @param {function} [callback]
         */
        tips: function (msg, time, callback) {
            return layui.layer.msg(msg, {
                time: (time || 3) * 1000,
                end: this._createCallback_(callback),
                shadeClose: true,
            })
        },
        /**
         * 加载提示，需要手动关闭
         * @param msg {string}
         * @param {function} [callback]
         */
        loading: function (msg = '', callback = null) {
            return msg ? layui.layer.msg(msg, {
                icon: 16,
                scrollbar: false,
                time: 0,
                end: this._createCallback_(callback),
            }) : layui.layer.load(2, {
                time: 0,
                scrollbar: false,
                end: this._createCallback_(callback),
            })
        },
        /**
         * 加载提示，并自动关闭
         * @param seconds {number} 秒数，默认为2
         */
        load: function (seconds = 2) {
            const index = layer.load(2, {time: seconds * 1000}); // 加载图标风格，并设置最长等待 10 秒
            layer.close(index);
        },
        // 关闭消息框
        close: function (index) {
            return layui.layer.close(index);
        },
        /**
         * 生成一个回调函数
         * @param {function} [callback]
         * @returns {*|(function(*): void)}
         * @private
         */
        _createCallback_: function (callback) {
            if (typeof callback === "function") {
                return callback;
            } else {
                return function (index) {
                    if (!admin.util.isEmpty(index)) {
                        this.close(index);
                    }
                }
            }
        }
    },
    /**
     * ajax 请求
     */
    ajax: {
        _isPosting: false,
        /**
         * 请求方法
         * @param option {{url?:string,data:Object,dataType:string,}}
         * @param {function} [ok] 返回成功回调
         * @param {function} [no] 返回错误回调
         * @param {function} [complete] 完成回调
         * @param {function} [ex] 错误回调味
         */
        get: function (option, ok, no, complete, ex) {
            this.ajax('get', option, ok, no, complete, ex)
        },
        /**
         * 请求方法
         * @param option {{url?:string,data:Object}}
         * @param {function} [ok] 返回成功回调
         * @param {function} [no] 返回错误回调
         * @param {function} [complete] 完成回调
         * @param {function} [ex] 错误回调
         */
        post: function (option, ok, no, complete, ex) {
            this.ajax('post', option, ok, no, complete, ex)
        },
        // layui.debounce(fn, wait) 防抖，layui.throttle(fn, wait) 节流
        /**
         *
         * @param option {{url?:string,data:Object}}
         * @param {function} [ok] 返回成功回调
         * @param {function} [no] 返回错误回调
         * @param {function} [complete] 完成回调
         * @param {function} [ex] 错误回调
         */
        postLimit: function (option, ok, no, complete, ex) {
            if (this._isPosting) {
                layui.layer.msg('操作过于频繁，请稍等一会');
                return;
            }
            this._isPosting = true;
            this.ajax('post', option, ok, no, function (data) {
                admin.ajax._isPosting = false;
                if (typeof complete == 'function') {
                    complete(data);
                }
            }, function (obj) {
                admin.ajax._isPosting = false;
                if (typeof ex === 'function') {
                    ex(obj);
                }
            })
        },
        /**
         * 请求方法
         * @param type {string} get|post|put|delete
         * @param option {{url?:string,data:Object,dataType?:string,}}
         * @param {function} [ok] 返回成功回调
         * @param {function} [no] 返回错误回调
         * @param {function} [complete] 完成回调
         * @param {function} [ex] 错误回调味
         */
        ajax: function (type, option, ok, no, complete, ex) {
            option = Object.assign({
                url: '',
                data: {},
                dataType: 'json',
                contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                timeout: 60000,
                statusName: 'code',
                statusCode: 0,
            }, option)
            if (option.url === '') {
                option.url = this.apiURL();
            }
            const loadIndex = admin.msg.loading('加载中')
            layui.jquery.ajax({
                url: option.url,
                type: type || 'get',
                data: option.data,
                dataType: option.dataType,
                contentType: option.contentType,
                timeout: option.timeout,
                headers: admin.config.ajax.headers(),
                success: function (res) {

                    if ([0, 200].includes(res[option.statusName])) {
                        if (typeof ok === 'string') {
                            admin.msg.success(ok);
                        } else if (res.msg) {
                            admin.msg.success(res.msg);
                        }
                        if (typeof ok == 'function') {
                            setTimeout(function () {
                                ok(res);
                            }, 500)
                        }
                    } else {
                        admin.msg.error(typeof no == 'string' ? no : res.msg);
                        typeof no == 'function' && no(res);
                    }
                },
                error: function (xhr, textstatus, thrown) {
                    admin.msg.error('Status:' + xhr.status + '，' + xhr.statusText + '，请稍后再试！', function () {
                        if (typeof ex === 'function') {
                            ex(this);
                        }
                    });
                },
                complete: function (xhr) {
                    admin.msg.close(loadIndex);
                    admin.config.ajax.refreshHeaders(option.type);
                    try {
                        const data = JSON.parse(xhr.responseText).data;
                        if (typeof complete === 'function') {
                            complete(data);
                        }
                    } catch (e) {
                        console.log('响应结果不符合规范:', e);
                    }
                }
            })
        },
        apiURL: function () {
            return location.origin + '/api' + location.pathname + location.search
        }
    },
    /**
     * 表单
     * @link https://layui.dev/docs/2/form/
     */
    form: {
        /**
         * 表单提交，注意必须为 lay-submit
         * <button class="layui-btn" lay-submit lay-filter="demo-submit">提交按钮</button>
         * @param {string} layFilter lay-filter 名称，如 demo-submit
         * @param {function} action 回调函数，接收表单中所填写的数据
         * @param {string} type 类型，默认为 submit，input[type=xxx]
         */
        onSubmit: function (layFilter, action, type = 'submit') {
            layui.form.on(`${type}(${layFilter})`, function (data) {
                const dataField = data.field;
                action(dataField);
                return false;
            })
        },
        /**
         * 绑定第一个 lay-submit 按钮，以提交第一个表单
         * @param {function|string} ok
         * @param {function} [postDataCallback]
         * @param {function} [complete]
         * @returns {boolean}
         */
        submitFirst: function (ok, postDataCallback, complete) {
            const formList = document.querySelectorAll("[lay-submit]");
            if (formList.length > 0) {
                const f = layui.jquery(formList[0]);
                const type = f.attr('data-type'); // 刷新
                let url = f.attr('lay-submit'), filter = f.attr('lay-filter');

                // 表格搜索不做自动提交
                if (type === 'tableSearch') {
                    return false;
                }
                // 自动添加过滤器
                if (filter === undefined || filter === '' || filter === null) {
                    filter = 'save_form_1';
                    f.attr('lay-filter', filter);
                }
                form.on('submit(' + filter + ')', function (data) {
                    let postData = data.field;
                    if (typeof postDataCallback == "function") {
                        postData = postDataCallback(postData);
                    }
                    if (postData === false) {
                        console.warn('取消了表单提交');
                        return false;
                    }
                    admin.form.request(url, postData, ok, null, complete, null)
                    return false;
                })

            }
        },
        request: function (url, data, ok, no, complete, ex) {
            if (url === undefined || url === '' || url === null) {
                url = location.origin + '/api' + location.pathname + location.search
            }
            admin.ajax.post({
                url, data
            }, function (data) {
                ok(data)
            }, no, complete, ex)
        },
        /**
         * 监听 select 事件
         * @param {string} filter select 上 lay-filter 的名称
         * @param {function} action 回调函数 {value:选中的值, data:{elem:原始DOM对象,othis:jQuery对象}}
         */
        listenSelect: function (filter, action) {
            form.on('select(' + filter + ')', function (data) {
                action({value: data.value, data});
            });
        }
    },
    /**
     * 页面处理
     */
    page: {
        /**
         * 刷新当前层所在父层数据
         * @param {{refreshTable?:boolean,refreshFrame?:boolean}} option
         * @returns {boolean}
         */
        closeCurrentOpen: function (option) {
            // console.log('admin.page.closeCurrentOpen',option)
            option = Object.assign({
                refreshTable: false,
                refreshFrame: false,
            }, option || {});

            const parentIndex = parent.layer.getFrameIndex(window.name);
            parent.layer.close(parentIndex);
            if (option.refreshTable) {
                // todo 获取表格 id
                parent.layui.table.reload()
            }
            if (option.refreshFrame) {
                parent.location.reload();
            }
            return true;
        },
        inIframe: function () {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        }
    },
    iframe: {
        /**
         * @link https://layui.dev/docs/2/layer/#options
         * @param {string} url
         * @param {{title?:string,width?:string,height?:string,full?:boolean,end?:Function,resize?:boolean}} options
         */
        open: function (url, options = {}) {
            if (admin.util.isEmpty(options.width) && admin.util.isEmpty(options.height)) {
                const width = document.body.clientWidth,
                    height = document.body.clientHeight;

                if (width >= 900 && height >= 600) {
                    options.width = '900px';
                    options.height = '600px';
                } else {
                    options.width = '100%';
                    options.height = '100%'
                }
                // console.log('iframe:', width, height, '=>', options.width, options.height)
            }
            if (options.full === true) {
                options.width = '100%';
                options.height = '100%';
            }
            let iFrameIndex = layui.layer.open({
                title: options.title || '', type: 2,
                area: [options.width, options.height],
                content: url,
                shadeClose: false,
                maxmin: true,
                end: function () {
                    if (typeof options.end === 'function') {
                        options.end(iFrameIndex)
                        iFrameIndex = null;
                    }
                }
            })
            if (options.resize) {
                $(window).on("resize", function () {
                    index && layer.full(iFrameIndex);
                })
            }

        },
        /**
         * 从父窗口中移除
         */
        closeFromParent: function (refresh = false) {
            const index = parent.layer.getFrameIndex(window.name);
            parent.layer.close(index);
            if (refresh) {
                localStorage.setItem('tao.refresh', "yes");
            }
        },
        hasRefresh: function (action) {
            if (localStorage.getItem('tao.refresh') === 'yes') {
                if (typeof action === 'function') {
                    action();
                }
                localStorage.removeItem('tao.refresh');
            }
        }
    },
    /**
     * 上传
     * the upload.run most code is from http://layuimini.99php.cn/,
     * @link https://layui.dev/docs/2/upload/
     */
    upload: {
        /**
         * 添加 圖片
         * @param {string} url 圖片地址
         * @param {string} name 操作來源 upload, picker
         */
        after: function (url, name) {
            // console.log('def:',url,name)
        },
        /**
         * 移除圖片
         * @param url
         */
        remove: function (url) {
        },
        /**
         * 執行上傳
         * @return this
         */
        run: function () {
            /**
             * @var admin {Object} 配置信息
             * @link src/public/extends/tao/layui/plugs/easy-admin/easy-admin.js upload:1363
             */
            const uploadList = document.querySelectorAll("[data-upload]"); // 上传
            const uploadSelectList = document.querySelectorAll("[data-upload-select]"); // 选择

            if (uploadList.length > 0) {
                layui.jquery.each(uploadList, function (i, v) {
                    // 上传配置
                    const jThis = $(this);
                    const uploadExts = jThis.attr('data-upload-exts') || 'png|jpg|jpeg',
                        uploadName = jThis.attr('data-upload'),
                        uploadNumber = jThis.attr('data-upload-number') || 'one', // 可选择图片数量
                        uploadSeparator = jThis.attr('data-upload-separator') || '|', // 分割符
                        uploadAccept = jThis.attr('data-upload-accept') || 'file',
                        uploadAcceptMime = jThis.attr('data-upload-mimetype') || '';
                    const elem = "input[name='" + uploadName + "']",
                        multiple = uploadNumber !== 'one',
                        uploadElem = this;
                    const headers = admin.config.ajax.headers();

                    // 监听上传事件
                    layui.upload.render({
                        elem: this,
                        url: admin.config.url.imageSave,
                        exts: uploadExts,
                        accept: uploadAccept,
                        acceptMime: uploadAcceptMime,
                        multiple: multiple,
                        headers: headers,
                        before: function (obj) {
                            layui.layer.load();
                        },
                        done: function (res) {
                            layui.layer.closeAll();
                            if (res.code === 0) {
                                let url = res.data;
                                if (multiple) { // 多张上传
                                    var oldUrl = $(elem).val();
                                    if (oldUrl !== '') {
                                        url = oldUrl + uploadSeparator + url;
                                    }
                                }

                                $(elem).val(url);
                                $(elem).trigger('input');
                                layui.layer.msg('上传成功', {
                                    icon: 1, time: 2000
                                })
                                admin.upload.after(url, 'upload');
                            }
                        },
                        error: function () {
                            setTimeout(function () {
                                layui.layer.closeAll()
                            }, 3000)
                        },
                        complete: function () {
                            admin.config.ajax.refreshHeaders('post');
                        }
                    })

                    // 监听上传 input 值变化;如果有值，则显示出图片
                    $(elem).bind('blur', function () {
                        admin.upload.after($(this).val(), 'blur');
                    });
                    $(elem).bind("input propertychange", function (event) {
                        const urlString = $(this).val(),
                            urlArray = urlString.split(uploadSeparator),
                            uploadIcon = $(uploadElem).attr('data-upload-icon') || "file";

                        $('#bing-' + uploadName).remove();
                        if (urlString.length > 0) {
                            const parant = $(this).parent('div');
                            let liHtml = '';
                            $.each(urlArray, function (i, v) {
                                liHtml += `<li>
<a><img src="${v}"></a>
<small class="uploads-delete-tip bg-red badge" data-upload-delete="${uploadName}" data-upload-url="${v}" data-upload-separator="${uploadSeparator}">×</small>
</li>`;
                            });
                            parant.after('<ul id="bing-' + uploadName + '" class="layui-input-block layuimini-upload-show">\n' + liHtml + '</ul>');
                        }

                    });

                    // 非空初始化，图片显示
                    if ($(elem).val() !== '') {
                        $(elem).trigger('input')
                    }
                });

                // 监听图片的删除事件
                layui.jquery('body').on('click', '[data-upload-delete]', function () {
                    const uploadName = $(this).attr('data-upload-delete'),
                        deleteUrl = $(this).attr('data-upload-url'),
                        sign = $(this).attr('data-upload-sign');

                    layui.layer.confirm('确定要删除吗？', function (index) {
                        const elem = "input[name='" + uploadName + "']";
                        const currentUrl = $(elem).val();
                        let url = '';
                        if (currentUrl !== deleteUrl) {
                            url = currentUrl.search(deleteUrl) === 0 ? currentUrl.replace(deleteUrl + sign, '') : currentUrl.replace(sign + deleteUrl, '');
                            $(elem).val(url);
                            $(elem).trigger("input");
                        } else {
                            $(elem).val(url);
                            $('#bing-' + uploadName).remove();
                        }
                        layui.layer.close(index);
                        admin.upload.remove(url);
                    });
                    return false;
                });
            }
// 图片选择
            if (uploadSelectList.length > 0) {
                layui.jquery.each(uploadSelectList, function (i, v) {
                    const uploadName = $(this).attr('data-upload-select'),
                        uploadNumber = $(this).attr('data-upload-number') || 'one',
                        uploadSeparator = $(this).attr('data-upload-separator') || '|';

                    const selectCheck = uploadNumber === 'one' ? 'radio' : 'checkbox',
                        inputElem = $("input[name='" + uploadName + "']"),
                        uploadElem = $(this).attr('id');

                    $('#' + uploadElem).off('click').on('click', function () {
                        admin.iframe.open(
                            admin.config.url.imageList + '?type=' + selectCheck, {
                                title: '图片选择',
                                end: function () {
                                    const images = localStorage.getItem('images');
                                    if (images && images.length > 0) {
                                        const urlArray = JSON.parse(images);
                                        const url = urlArray.join(uploadSeparator);
                                        inputElem.val(url);
                                        inputElem.trigger("input");
                                        admin.msg.success('选择成功');
                                        localStorage.removeItem('images');
                                        admin.upload.after(url, 'picker');
                                    }
                                }
                            });
                    });
                });

            }
            return this;
        }
    },
    /**
     * 表格数据
     * @link https://layui.dev/docs/2/table/
     */
    table: {
        /**
         * table 的配置信息
         * @private
         */
        _config: {
            id: 'table',
            url: '',
            autoApi: true,
            tableId: null, // 在 table.render 时会自动赋值
            key: 'id'
        },
        /**
         * 获取配置信息
         * @param config {Object} 配置值
         * @param key {string}
         * @param {any} defV 默认值
         */
        getConfig: function (config, key, defV = null) {
            if (!admin.util.isEmpty(config) && !admin.util.isEmpty(config[key])) {
                return config[key];
            }
            return this._config[key] || defV;
        },
        /**
         * 初始化
         * @param {{id?:string, url:string}} [config]
         * @return this;
         */
        with: function (config = {}) {
            Object.assign(this._config, {url: admin.ajax.apiURL()}, config)
            return this;
        },
        getTableId: function () {
            return this._config.tableId;
        },
        /**
         * 表格渲染<pre>
         * lineStyle: 'height: 95px;' 多行樣式
         * </pre>
         * @param {{toolbar?:string,url?:string,cols:Array,page?:boolean,lineStyle?:string}} options 表格 render 时配置信息
         * @param {{search?:boolean}} [additions] 其它配置信息
         * @return this
         */
        render: function (options, additions = {}) {
            const tableId = this._config.id;

            const config = Object.assign({
                elem: '#' + tableId,
                url: admin.ajax.apiURL(),
                defaultToolbar: ['filter', {
                    title: '搜索',
                    layEvent: 'search',
                    icon: 'layui-icon-search'
                }],
                page: true,
            }, options)
            if (config.page && admin.util.isEmpty(config['limit'])) {
                config['limit'] = 15;
                config['limits'] = [1, 15, 30, 50];
            }
            const adds = Object.assign({
                search: true,
            }, additions)
            const currentTable = layui.table.render(config);

            // 搜索框，需要指定的格式
            const tableSearchElem = $('#' + tableId + '-search');
            if (adds.search) {
                if (tableSearchElem.length === 1) {
                    // 重置按钮
                    const resetElem = tableSearchElem.find('button[type=reset]');
                    if (resetElem.length === 1) {
                        resetElem.bind('click', function () {
                            currentTable.reloadData({
                                where: {reset: 1},
                                page: {curr: 1}
                            })
                        })
                    } else {
                        console.log('没有找到重置按钮 <button type="reset" class="layui-btn layui-btn-primary">重置</button>')
                    }
                    // 搜索按钮
                    const submitElem = tableSearchElem.find('a[lay-submit]');
                    if (submitElem.length === 1) {
                        submitElem.bind('click', function (e) {
                            e.stopPropagation();
                            e.preventDefault();
                            // <form className="layui-form layui-form-pane form-search"  lay-filter="form-search"></form>
                            const data = form.val('form-search');
                            // console.log(data);
                            currentTable.reloadData({
                                where: data,
                                page: {curr: 1}
                            })
                        })
                    } else {
                        console.log('没有找到提交按钮 <a class="layui-btn layui-btn-normal" lay-submit>搜索</a>')
                    }

                    // 显示/隐藏搜索框
                    table.on('toolbar(' + tableId + ')', function (obj) {
                        switch (obj.event) {
                            case 'search':
                                if (tableSearchElem.hasClass('layui-hide')) {
                                    tableSearchElem.removeClass('layui-hide')
                                } else {
                                    tableSearchElem.addClass('layui-hide');
                                }
                                // tableSearchElem.toggle();
                                break;
                        }
                    });
                } else {
                    console.log('开启了条件搜索，但没有找到 <fieldset class="table-search-fieldset" id="' + tableId + '-search">')
                }
            }
            // 工具栏事件
            layui.util.on('lay-on', {
                // 刷新按钮
                refresh: function () {
                    // admin.msg.load();
                    currentTable.reloadData()
                },
            })
            this._config.tableId = currentTable;
            return this;
        },
        /**
         * 监听一些常用的 lay-on 事件，如 工具栏的 batchDelete/create，行记录的 edit/delete
         * @param {{url?:string,}} [config] 配置信息
         * @return this
         */
        addLayOnActions: function (config) {
            const tableId = this._config.id;
            const url = config && config.url ? config.url : this._config.url;
            // 批量删除
            const events = {
                // 批量删除（工具栏）
                batchDelete: function () {
                    const rows = table.checkStatus(tableId);
                    if (rows.data.length < 1) {
                        admin.msg.error('没有选中任何记录!')
                    } else {
                        const ids = rows.data.map(r => r.id);
                        admin.msg.confirm('确定要删除这些记录吗？', function () {
                            admin.ajax.postLimit({
                                url: '/api' + url + '/delete', data: {id: ids.join(',')},
                            }, function () {
                                layui.table.reload(tableId);
                            })
                        })
                    }
                },
                // 添加记录（工具栏）
                create: function () {
                    admin.iframe.open(url + '/add', {
                        title: '添加记录',
                        end: function () {
                            admin.iframe.hasRefresh(() => {
                                layui.table.reload(tableId);
                            })
                        }
                    })
                },
                // 记录编辑（行操作）
                edit: function () {
                    const id = parseInt($(this).attr('data-id'));
                    admin.iframe.open(url + '/edit?id=' + id, {
                        title: '编辑记录', end: function () {
                            admin.iframe.hasRefresh(() => {
                                layui.table.reload(tableId);
                            })
                        }
                    });
                },
                // 删除记录（行操作）
                delete: function () {
                    const id = parseInt($(this).attr('data-id'));
                    admin.msg.confirm('确定要删除当前记录吗！', function () {
                        admin.ajax.postLimit({
                                url: '/api' + url + '/delete', data: {id},
                            },
                            function () {
                                admin.table.removeWith(tableId, s => s.id === id)
                            }
                        )
                    })
                }
            };

            layui.util.on('lay-on', events)
            return this;
        },
        /**
         * 监听行操作事件 lay-event，通常绑定在 table.cols.toolbar 上
         * @param {{url?:string, action?:Function, key?:string}} [config] 回调函数，obj.event 是事件名称, obj.data 是当前行数据
         * @return this
         */
        addRowToolEvents: function (config) {
            const tableId = this._config.id;
            const url = this.getConfig(config, 'url');
            const key = this.getConfig(config, 'key', 'id');

            layui.table.on('tool(' + tableId + ')', function (obj) {
                const keyV = obj.data[key];
                switch (obj.event) {
                    case 'edit':
                        admin.iframe.open(url + '/edit?' + key + '=' + keyV, {
                            title: '编辑记录', end: function () {
                                admin.iframe.hasRefresh(() => {
                                    layui.table.reloadData(tableId);
                                })
                            }
                        });
                        return;
                    case 'delete':
                    case 'remove':
                        admin.msg.confirm('确定要删除当前记录吗！', function () {
                            admin.ajax.postLimit({
                                    url: '/api' + url + '/delete', data: {
                                        [key]: keyV,
                                    },
                                },
                                function () {
                                    admin.table.removeWith(s => s[key] === keyV)
                                }
                            )
                        })
                        return;
                    default:
                        if (typeof config.action === 'function') {
                            config.action(obj.data);
                        } else {
                            console.warn('没有添加事件处理函数:', obj.event)
                        }
                }
            })
            return this;
        },

        icon: function (data) {
            const v = data[this.field];
            return `<i class="${v}"></i>`
        },
        image: function (data) {
            const option = {
                imageWidth: this.imageWidth || 200,
                imageHeight: this.imageHeight || 40,
                imageSplit: this.imageSplit || '|',
                imageJoin: this.imageJoin || '<br>',
                title: this.title || this.field,
                field: this.field,
                value: data[this.field],
            }
            if (option.value === undefined || option.value === null) {
                return '<img style="max-width: ' + option.imageWidth + 'px; max-height: ' + option.imageHeight + 'px;" src="' + option.value + '" data-image="' + option.title + '">';
            } else {
                const values = option.value.split(option.imageSplit),
                    valuesHtml = [];
                values.forEach((value, index) => {
                    valuesHtml.push('<img onclick="admin.layer.image(\'' + value + '\')" style="max-width: ' + option.imageWidth + 'px; max-height: ' + option.imageHeight + 'px;" src="' + value + '">');
                });
                return valuesHtml.join(option.imageJoin);
            }
        },
        /**
         * switch 模板
         * @param data {Object} 配置信息
         * @return string
         */
        switch: function (data) {
            const option = {
                field: this.field,
                value: data[this.field],
                selectList: this.selectList || {0: '禁用', 1: '启用'},
                tips: this.tips || '开|关',
                filter: this.filter || this.field
            }
            const key = admin.table._config.key;
            if (admin.util.isEmpty(data[key])) {
                return '';
            }
            const checked = [1, "1"].includes(option.value) ? 'checked' : '';
            // console.log(data,option,checked)
            return `<input type="checkbox" name="${option.field}" value="${data[key]}"
lay-skin="switch" lay-text="${option.tips}" lay-filter="${option.filter}" ${checked}>`;
        },
        humanTime: function (data) {
            const v = data[this.field];
            if (admin.util.isEmpty(v)) {
                return '-';
            }
            const date = new Date(v * 1000);  // 参数需要毫秒数，所以这里将秒数乘于 1000
            return [
                date.getFullYear(),
                ('' + (date.getMonth() + 1)).padStart(2, '0'),
                ('' + date.getDate()).padStart(2, '0')
            ].join('-') + ' ' + [
                ('' + date.getHours()).padStart(2, '0'),
                ('' + date.getMinutes()).padStart(2, '0'),
                ('' + date.getSeconds()).padStart(2, '0')
            ].join(':')
        },
        /**
         * 监听 switch
         * @param field {string}
         * @param action {Function} 回调 <pre>{
         *     value:当前值(ID), checked: 选中状态, obj: $对象用于获取其它属性
         * }</pre>
         * @link https://layui.dev/docs/2/form/checkbox.html#on
         */
        listenSwitch: function (field, action) {
            layui.form.on('switch(' + field + ')', function (data) {
                const elem = data.elem; // 获得 checkbox 原始 DOM 对象
                const othis = data.othis; // 获得 checkbox 元素被替换后的 jQuery 对象
                action({value: elem.value, checked: elem.checked, obj: othis})
            })
        },
        /**
         * 提交 switch
         * @param {Array} fields 字段名称
         * @param {{url:string}} [config] 配置信息
         * @return this
         */
        addPostSwitch: function (fields = ['status'], config = {}) {
            const url = this.getConfig(config, 'url');
            const key = this.getConfig(config, 'key', 'id');

            fields.forEach(field => {
                if (admin.util.isEmpty(field)) {
                    console.warn('listenSwitch empty field')
                    return;
                }
                admin.table.listenSwitch(field, function (data) {
                    const postData = {
                        [key]: data.value, field, value: data.checked ? 1 : 2
                    }
                    admin.ajax.post({
                        url: url + '/modify',
                        data: postData,
                    })
                })
            })


            return this;
        },
        /**
         * 监听编辑单元
         * @param {string} tableId 表格 ID
         * @param {Function} callback 回调 <pre> {
         *  field: 修改的字段, value: 修改后的值, oldValue: 修改前的值,data: 所在行的数据
         * }</pre>
         */
        listenEditText: function (tableId, callback) {
            table.on('edit(' + tableId + ')', callback)
        },
        /**
         * 提交 编辑单元 EditText
         * @param {{url?:string,ok?:Function}} [config] 配置信息
         * @return this
         */
        addPostEditText: function (config) {
            const tableId = this._config.id;
            const url = config && config.url ? config.url : this._config.url;

            admin.table.listenEditText(tableId, function (obj) {
                const postData = {
                    id: obj.data.id, field: obj.field, value: obj.value,
                }
                admin.ajax.post({
                    url: url + '/modify',
                    data: postData,
                }, function () {
                    if (config && typeof config.ok === "function") {
                        config.ok(postData);
                    }
                })
            })
            return this;
        },
        /**
         * 移除指定的数据，并重载数据
         * @param predicate
         * @return this
         */
        removeWith: function (predicate) {
            const tableId = this._config.id;
            const rows = layui.table.cache[tableId];
            const index = rows.findIndex(predicate);
            if (index > -1) {
                rows.splice(index, 1);
                layui.table.renderData(tableId);
            } else {
                admin.msg.error('没有找到需要删除的数据项')
            }
            return this;
        }
    },
    /**
     * 日期
     * @link https://layui.dev/docs/2/laydate
     */
    date: {
        /**
         * 日期绑定
         * <input name="create_time" value="" id="create_time" class="layui-input">
         * @param {string} id ID 名称
         * @param {boolean} range 是否为范围，默认为 true
         */
        renderDate: function (id, range = true) {
            layui.laydate.render({
                elem: '#' + id,
                type: 'date',
                range,
            })
        },
        renderDatetime: function (id) {
            layui.laydate.render({
                elem: '#' + id,
                type: 'datetime'
            });
        }
    },
    /**
     * 缓存
     * @link https://layui.dev/docs/2/base.html#data
     */
    cache: {
        table: 'phax',
        /**
         * 读取缓存
         * @param {string} key
         * @param {any} defV 默认值
         */
        read: function (key, defV) {
            const local = layui.data(this.table);
            const v = local[key];
            if (admin.util.isEmpty(v)) {
                return defV;
            }
            return JSON.parse(v);
        },
        save: function (key, data) {
            layui.data(this.table, {
                key: key, value: JSON.stringify(data),
            })
        },
        remove: function (key) {
            layui.data(this.table, {
                key: key, remove: true,
            })
        },
        clear: function () {
            localStorage.clear();
        }
    }
};