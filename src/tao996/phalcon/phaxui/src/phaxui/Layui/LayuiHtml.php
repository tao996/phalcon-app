<?php

namespace Phaxui\Layui;

class LayuiHtml
{

    public static function upload(string $label, string $name, array $options = [
        'value' => '', 'type' => 'hidden'
    ])
    {
        $options = array_merge([
            'value' => '',
            'number' => 'one',
            'ext' => 'png|jpg|ico|jpeg',
            'placeholder' => '', 'tip' => '',
            'type' => 'hidden',
            'class' => '',
            'required' => false,
        ], $options);
        $required = $options['required'] ? 'required' : '';
        echo <<<HTML
<div class="btn-image-upload {$options['class']}">
    <label class="layui-form-label {$required}">{$label}</label>
    <div class="layui-input-block">
        <input class="layui-input" name="{$name}" id="{$name}" type="{$options['type']}" style="margin-bottom: 10px;" value="{$options['value']}" placeholder="{$options['placeholder']}" />
        <div style="margin-bottom: 10px;">
    <span><a class="layui-btn" 
data-upload="{$name}" 
data-upload-number="{$options['number']}"
data-upload-exts="{$options['ext']}"
><i class="fa fa-upload"></i> 上传</a></span>
    <span style="margin-left: 6px;"><a class="layui-btn layui-btn-normal" id="select_{$name}"
data-upload-select="{$name}"
data-upload-number="{$options['number']}"
><i class="fa fa-list"></i> 选择</a></span>
<span class="hint" style="margin-left: 10px;">{$options['tip']}</span>
        </div>
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
            'src' => url('tao/auth/captcha'),
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
}