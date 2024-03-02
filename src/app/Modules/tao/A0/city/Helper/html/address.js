const point = $('#pointText')
admin.util.layOn({
    splitPointText: function () {
        const text = point.val();
        if (admin.util.isEmpty(text.trim())) {
            admin.layer.msg('坐标点内容不能为空');
        } else {
            const pp = text.split(',');
            if (pp.length != 2) {
                admin.layer.error('请检查坐标点内容是否正确');
            } else {
                admin.form.patch('near', {
                    lng: pp[0], lat: pp[1],
                })
                admin.layer.success('填写坐标成功');
            }
        }
    }
})