admin.util.layOn({
    getCurrent:function (){
        console.log('IP 未设置');
        return;
        admin.ajax.post({data:{action:'getCurrent'}},function (data){
            console.log('...',data)
        })
    }
})
admin.form.submitFirst(() => {
    admin.iframe.closeFromParent(true);
}, function (data) {
    return Object.assign(data, {})
})

const App = {
    data(){
        return {}
    },
    methods:{
        openMsg(){
            console.log('open')
        }
    }
}
const app = Vue.createApp(App);
app.mount('#tao-app')