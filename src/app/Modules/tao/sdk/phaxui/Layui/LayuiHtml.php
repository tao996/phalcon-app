<?php

namespace App\Modules\tao\sdk\phaxui\Layui;

class LayuiHtml
{
    /**
     * 上传组
     * @param string $label 标题，如 “封面”
     * @param string $name input name，如 cover
     * @param array $options 配置信息 [value=>默认值, number=>one单张图片/other多选, ext=>png|jpg|ico|jpeg, type=>hidden输入框, placeholder=>输入框提示文字, tip=>提示文字,class=>顶层div样式,required=>false]
     * @return void
     */
    public static function upload(string $label, string $name, array $options = [
        'value' => '', 'type' => 'hidden'
    ])
    {
        $options = array_merge([
            'value' => '',
            'number' => 'one',
            'ext' => 'png|jpg|ico|jpeg',
            'placeholder' => '图片地址', 'tip' => '',
            'type' => 'input',
            'class' => '',
            'required' => false,
            'float' => true,
        ], $options);
        $requiredHTML = $options['required'] ? 'required' : '';
        // 输入提示
        $tipDivHTML = $options['tip'] ? '<div class="hint" style="margin-top: 5px;">' . $options['tip'] . '</div>' : '';
        $floatLeft = $options['float'] ? 'display:inline-block;float:left;' : '';
        // 输入框
        $inputHTML = $options['number'] == 'one' ? '<input class="layui-input" name="' . $name . '" id="' . $name . '" type="hidden" style="margin-bottom: 10px;" value="' . $options['value'] . '" placeholder="' . $options['placeholder'] . '" />' : '';
        $editBtnHTML = $options['number'] == 'one' && $options['type'] == 'input' ? '<a class="layui-btn layui-btn-normal data-upload-img-edit" data-name="' . $name . '"><i class="layui-icon layui-icon-edit"></i></a>' : '';

        echo <<<HTML
<div class="btn-image-upload {$options['class']}" style="margin-bottom: 10px; {$floatLeft}">
    <label class="layui-form-label {$requiredHTML}">{$label}</label>
    <div class="layui-input-block"> {$inputHTML}
<div style="margin-bottom:0;">
<a class="layui-btn layui-btn-normal" id="select_{$name}"
data-upload-select="{$name}"
data-upload-number="{$options['number']}"
><i class="fa fa-list"></i></a>
{$editBtnHTML}
    <a class="layui-btn" 
data-upload="{$name}" 
data-upload-number="{$options['number']}"
data-upload-exts="{$options['ext']}"
><i class="fa fa-upload"></i></a>
 
</div>
{$tipDivHTML}
    </div>
</div>
HTML;
    }

    public static function icon(array $options = [])
    {
        $options = array_merge([
            'value' => 'fa fa-list',
        ], $options);
        echo <<<HTML
    <div class="layui-form-item">
        <label class="layui-form-label">选择图标</label>
        <div class="layui-input-inline">
            <input type="text" id="icon" name="icon"
                   class="layui-input" value="{$options['value']}">
        </div>
        <div class="layui-form-label">预览</div>
        <div class="layui-form-mid layui-text-em">
            <i id="preview" class="{$options['value']}"></i>
        </div>
        
   
    </div>  

    <div class="layui-form-item">
    <label class="layui-form-label"></label>
    <div class="layui-input-block">
               <div class="hint">此样式填写于 &lt;i class="图标样式">&lt;/i>，参考资料: <a
                    href="https://layui.dev/docs/2/icon/#examples" target="_blank">Layui Icon</a>，
            <a href="https://fontawesome.com/v4/icons/" target="_blank">FontAwesome 4.7</a></div>
</div>
    </div>
HTML;
    }

    public static function iconJs()
    {
        echo '<script>';
        echo <<<JS
const preview = $('#preview');
$('#icon').bind('change', function () {
    const v = $(this).val();
    preview.removeClass().addClass(v);
});
JS;
        echo '</script>';

    }

    public static function captcha(array $options = []): string
    {
        $options = array_merge([
            'name' => 'captcha',
            'placeholder' => '验证码',
            'src' => url('tao/captcha'),
            'title' => '点击刷新验证码图片'
        ], $options);

        return <<<HTML
<div class="layui-form-item">
    <div class="layui-row">
        <div class="layui-col-xs7">
            <div class="layui-input-wrap">
                <div class="layui-input-prefix">
                    <i class="layui-icon layui-icon-vercode"></i>
                </div>
                <input type="text" name="{$options['name']}" value="" lay-verify="required" placeholder="{$options['placeholder']}"
                       lay-reqtext="{$options['placeholder']}" autocomplete="off" class="layui-input" lay-affix="clear">
            </div>
        </div>
        <div class="layui-col-xs5">
            <div style="margin-left: 10px;height: 38px;overflow: hidden;">
                <img style="width: 100%;" title="{$options['title']}"
                     src="{$options['src']}"
                     onclick="this.src='{$options['src']}?t='+ new Date().getTime();">
            </div>
        </div>
    </div>
</div>
HTML;
    }

    public static function csrfToken(): void
    {
        Layui::getInstance()->addWindowConfig([
            'CSRF_TOKEN' => join('.', [security()->getTokenKey(), security()->getToken()])
        ]);
    }

    /**
     * 驗證 CSRF
     * @param bool $destroyIfValid 默認為 false，主要考慮通常會有其它業務邏輯需要處理；如果設置為 true，可能需要手動刷新表單或更換 token
     * @return void
     * @throws \Exception
     */
    public static function checkCsrfToken(bool $destroyIfValid = false): void
    {
        $token = request()->getHeader('X-Csrf-Token');
        if (empty($token)) {
            throw new \Exception('CSRF TOKEN is empty');
        }
        $items = explode('.', $token);
        if (count($items) != 2) {
            throw new \Exception('CSRF TOKEN value not invalid');
        }
        $_POST[$items[0]] = $items[1];
        if (!security()->checkToken(null, null, $destroyIfValid)) {
            throw new \Exception('CSRF TOKEN invalid：');
        }

    }

    private static $breadcrumbItems = [];

    /**
     * 设置面包屑导航
     * @param array|string $menus 需要添加的菜单，格式支持 <br/>
     * 标题 <br>
     * [ 'href'=>'链接','text'=>标题 ] 或者 <br/>
     * [ ['href'=>'x1','text'=>'A1'], ['href'=>'x2','text'=>'A2'] ]
     * @return void
     */
    public static function addBreadcrumbItem(array|string $menus)
    {
        if ($menus) {
            if (is_string($menus)) {
                $menus = ['text' => $menus];
            }
            // 格式检查
            if (isset($menus['text'])) {
                self::$breadcrumbItems[] = ['href' => $menus['href'] ?? '', 'text' => $menus['text']];
            } else {
                foreach ($menus as $menu) {
                    self::$breadcrumbItems[] = ['href' => $menu['href'] ?? '', 'text' => $menu['text']];
                }
            }
        }
    }

    /**
     * 输出面包屑导航
     * @return string
     */
    public static function breadcrumb(): string
    {
        if (self::$breadcrumbItems) {
            $html = ['<div class="layui-breadcrumb"><a href="/">首页</a>'];
            $items = self::$breadcrumbItems;
            for ($i = 0; $i < count($items) - 1; $i++) {
                $html[] = $items[$i]['href']
                    ? "<a href='{$items[$i]['href']}'>{$items[$i]['text']}</a>"
                    : "<a><cite>{$items[$i]['text']}</cite></a>";
            }
            $last = end($items);
            $html[] = "<a><cite>{$last['text']}</cite></a>";

            $html[] = '</div>';
            return join('', $html);
        } else {
            return '';
        }
    }
}