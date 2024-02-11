admin.util.layOn({
    term: function (othis) {
        const dto = othis.attr('data-to');
        admin.iframe.open('/tao.city/admin.helper/term', {
            title: '球队选择', end: function () {
                const term = admin.storage.getFirst('terms');
                if (term) {
                    if ('main' === dto) {
                        admin.form.updateValueByName({'metadata[id1]': term.id, 'metadata[name1]': term.name});
                    } else {
                        admin.form.updateValueByName({'metadata[id2]': term.id, 'metadata[name2]': term.name});
                    }
                }
            }
        })
    }
});