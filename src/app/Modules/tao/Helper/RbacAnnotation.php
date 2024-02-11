<?php

namespace app\Modules\tao\Helper;


use app\Modules\tao\Models\SystemNode;
use Phax\Foundation\Router;
use Phax\Helper\AnnotationDocCommentParse;
use Phax\Helper\ServicesJSON;

/**
 * 分析模 RBAC 注解信息, 没有使用 rbac 标记的操作器或者操作不会出现在结果中 <pre>
 * 在模块/控制器/控制中使用 js JSON 格式来标注 rbac ({title:'名称’,close:1})
 * 使用时注意：
 *  1。如果 close (使用 isset('close') 判断 )写在控制器中，则表示整个控制器下的 Action 都默认不开启授权
 *  如果 close 写在 Action 上，则表示当前 Action 默认不开启授权
 *  2。没有标记的 Controller/Action 默认只有超级管理员能够访问
 * </pre>
 * @link https://docs.phalcon.io/5.0/en/annotations
 */
class RbacAnnotation
{

    private AnnotationDocCommentParse $parse;
    /**
     * @var bool 是否忽略默认的前端（即 app/Http）
     */
    private bool $ignoreDefaultApp = false;
    /**
     * @var string 需要分析的前端（即 app/Http/Projects/xxx）
     */
    private string $project = '';
    /**
     * @var array 需要忽略的模块
     */
    private array $ignoreModules = [];

    public function __construct()
    {
        $this->parse = new AnnotationDocCommentParse();
    }

    /**
     * 生产环境下，自动忽略
     * @return $this
     */
    public function autoProduct(): self
    {
        $this->ignoreDefaultApp = true;
        $this->project = config('app.project');
        $this->addIgnoreModule('demo');

        return $this;
    }

    /**
     * 加载全部节点信息
     * @return array
     */
    public function loadNodes(): array
    {
        $info = $this->getSingleAppInfos();
        $rows = $this->loadRbacInfo($info);

        $mInfo = $this->getMultiModulesInfos();
        $mRows = $this->loadMultiModulesRbacInfo($mInfo);

        return array_merge($rows, $mRows);
    }

    /**
     * 忽略默认的单应用，此时通常是因为启用了 app.project
     * @return $this
     */
    public function ignoreDefaultApp(): static
    {
        $this->ignoreDefaultApp = true;
        return $this;
    }

    /**
     * 只分析此指定 project
     * @param string $name
     * @return $this
     */
    public function setProject(string $name): static
    {
        $this->project = $name;
        return $this;
    }

    /**
     * 忽略指定的多模块
     * @param string $module
     * @return $this
     */
    public function addIgnoreModule(string $module)
    {
        if (!in_array($module, $this->ignoreModules)) {
            $this->ignoreModules[] = $module;
        }
        return $this;
    }

    public function loadRbacInfo(array $info): array
    {
        $rows = []; // system_node 表记录

        // 多模块          module/controller/action
        // +子目录         module/subC.controller/action
        // +子模块         module.subM/controller/action
        // +子模块+子目录   module.subM/subC.controller/action
        // 单应用          controller/action
        // +子目录         subC.controller/action
        // +子模块+子目录   subM/subC.controller/action
        $isMulti = isset($info['name']);
        $name = $isMulti ? Router::formatName($info['name']) : '';
        if (isset($info['controllers'])) {
            if ($cc = $this->getControllerNodes(
                $info['namespace'] . '\Controllers',
                $info['controllers'],
                $name, '')
            ) {
                $rows = array_merge($rows, $cc);
            }
        }
        if (isset($info['extends'])) {
            foreach ($info['extends'] as $subM => $ctrl) {
                if ($cc = $this->getControllerNodes(
                    $info['namespace'] . '\\A0\\' . $subM . '\\Controllers',
                    $ctrl,
                    $name,
                    Router::formatName($subM)
                )) {
                    $rows = array_merge($rows, $cc);
                }
            }
        }
        if ($isMulti) {
            array_unshift($rows, [
                'module' => $name, 'node' => $name,
                'title' => $info['title'] ?? $info['name'],
                'type' => SystemNode::TYPE_MODULE,
                'is_auth' => isset($info['close']) ? 0 : 1,
            ]);
        }

        return $rows;
    }

    /**
     * @param string $namespace 命名空间
     * @param array $controllers 控制器列表
     * @param string $module 多模块名称
     * @param string $subM 子模块名称
     * @return array
     * @throws \ReflectionException
     */
    public function getControllerNodes(string $namespace, array $controllers, string $module = '', string $subM = '')
    {
        $rows = [];
        $isMulti = $module != '';
        $isSubM = $subM != '';
//dd($module,$subM,'~~');
        foreach ($controllers as $ctrl) {
            $isSubC = is_array($ctrl);
            $controller = $isSubC ? $ctrl[1] : $ctrl;
            $controllerName = substr($controller, 0, -4); // 去掉 .php
            $ctrlName = Router::formatName($controller);

            $controllerNamespace = join('\\', $isSubC
                ? [$namespace, $ctrl[0], $controllerName]
                : [$namespace, $controllerName]
            );

            $rbac = $this->getControllerInfo($controllerNamespace);
//            dd($controllerNamespace, $rbac);
            // 没有操作，跳过
            if (empty($rbac) || empty($rbac['actions'])) {
                continue;
            }

            // 控制器
            $ctrlNode = $isSubC ? join('.', [$ctrl[0], $ctrlName]) : $ctrlName;
            if ($isMulti) { // 多模块
                if ($isSubM) {
                    $ctrlNode = $module . '.' . $subM . '/' . $ctrlNode;
                } else {
                    $ctrlNode = $module . '/' . $ctrlNode;
                }
            } else if ($isSubM) { // 子模块
                $ctrlNode = $subM . '/' . $ctrlNode;
            }
            $rows[] = [
                'module' => $module,
                'node' => $ctrlNode,
                'title' => $rbac['title'],
                'type' => SystemNode::TYPE_CONTROLLER,
                'is_auth' => isset($rbac['close']) ? 0 : 1
            ];
//            dd($rows);
            foreach ($rbac['actions'] as $action => $actionRbac) {
                $rows[] = [
                    'module' => $module,
                    'node' => join('/', [$ctrlNode, $action]),
                    'title' => $actionRbac['title'],
                    'type' => SystemNode::TYPE_ACTION,
                    'is_auth' => isset($rbac['close']) || isset($actionRbac['close']) ? 0 : 1,
                ];
            }
        }
        return $rows;
    }

    /**
     * 分析前端模块的结构信息
     * @param string $project
     * @return array 返回结果可供 $this->>loadRbacInfo 使用
     */
    public function getSingleAppInfos(string $project = ''): array
    {
        $rows = [];
        if (empty($project)) {
            $project = $this->project;
        }
        // 单应用
        if (empty($project)) {
            if ($this->ignoreDefaultApp) {
                return [];
            }
            $path = PATH_APP . 'Http';
            $rows['namespace'] = 'app\Http';
        } else {
            $path = PATH_APP . 'Http/Projects/' . $project;
            $rows['namespace'] = 'app\Http\Projects\\' . $project;
        }
        $rows['path'] = $path;
        $rows['controllers'] = $this->scanDirController($path . '/Controllers');
        $rows['extends'] = $this->scanExtends($path . '/A0');
        return $rows;
    }

    private function excludeFileNames($name): bool
    {
        return in_array($name, ['.', '..']);
    }

    /**
     * 分析多模块下全部模块的结构信息
     * @return array 返回结果可供 $this->>loadRbacInfo 使用
     */
    public function getMultiModulesInfos(): array
    {
        $rows = [];
        foreach (scandir(PATH_APP_MODULES) as $name) {
            if (in_array($name, $this->ignoreModules)) {
                continue;
            }
            if (!$this->excludeFileNames($name) && is_dir(PATH_APP_MODULES . $name)) {
                $rows[$name] = $this->scanModule($name);
            }
        }
        return $rows;
    }

    public function loadMultiModulesRbacInfo(array $info): array
    {
        $rows = [];
        foreach ($info as $item) {
            $rows = array_merge($rows, self::loadRbacInfo($item));
        }
        return $rows;
    }

    /**
     * 扫描模块下的控制器，并返回模块结构信息
     * @param string $name 模块名称
     * @return array ['name'=>多模块名称, 'path'=>路径','controllers'=>[控制器列表], 'extends'=>['subM1'=>[控制器列表],...]]
     */
    public function scanModule(string $name): array
    {
        $rows = ['name' => $name, 'namespace' => 'app\Modules\\' . $name]; // 多模块标记
        $pathModule = PATH_APP_MODULES . $name;
        if (file_exists($pathModule . '/Module.php')) {
            if ($mInfo = $this->getAnnotationForClass($rows['namespace'] . '\Module')) {
                $rows['title'] = $mInfo['title'] ?? $rows['name'];
            }
        }
        $rows['path'] = $pathModule;
        $pathModuleControllers = $pathModule . '/Controllers';
        $rows['controllers'] = $this->scanDirController($pathModuleControllers);
        $rows['extends'] = [];
        $extends = $this->getExtendsDirs($pathModule . '/A0');
        foreach ($extends as $extend) {
            $extendControllers = $pathModule . '/A0/' . $extend . '/Controllers';
            $rows['extends'][$extend] = $this->scanDirController($extendControllers);
        }
        return $rows;
    }

    /**
     * 扫描扩展目录，并返回所包含的控制器文件
     * @param string $pathExtends 扩展目录路径
     * @return array 所包含的控制器文件列表 ['subM1'=>[....], 'subM2'=>[....], ...]
     */
    private function scanExtends(string $pathExtends): array
    {
        $rows = [];
        $extends = $this->getExtendsDirs($pathExtends);
        foreach ($extends as $extend) {
            $extendControllers = $pathExtends . '/' . $extend . '/Controllers';
            $rows[$extend] = $this->scanDirController($extendControllers);
        }
        return $rows;
    }

    /**
     * 扫描模块下的扩展目录，并返回子模块名称列表
     * @param string $pathExtends 模块位置，通常为 /xxx/A0
     * @return array 子模块名称列表 ['subM1', 'subM2', 'subM3',...]
     */
    private function getExtendsDirs(string $pathExtends): array
    {
        $rows = [];
        if (is_dir($pathExtends)) {
            foreach (scandir($pathExtends) as $name) {
                if (!$this->excludeFileNames($name)) {
                    if (is_dir($pathExtends . '/' . $name)) {
                        $rows[] = $name;
                    }
                }
            }
        }
        return $rows;
    }

    /**
     * 扫描控制器目录，并返回控制器文件名列表
     * @param string $pathControllers 必须为 /xxx/Controllers
     * @return array ['XxxController.php', ['sub','XxxController.php],...]
     */
    private function scanDirController(string $pathControllers): array
    {
        $rows = [];
        if (is_dir($pathControllers)) {
            foreach (scandir($pathControllers) as $ctrl) {
                if (!$this->excludeFileNames($ctrl)) {
                    if (str_ends_with($ctrl, 'Controller.php')) {
                        $rows[] = $ctrl;
                    } elseif (is_dir($pathControllers . '/' . $ctrl)) { // 子目录
                        foreach (scandir($pathControllers . '/' . $ctrl) as $subCtrl) {
                            if (str_ends_with($subCtrl, 'Controller.php')) {
                                $rows[] = [
                                    $ctrl,
                                    $subCtrl
                                ];
                            }
                        }
                    }
                }
            }
        }
        return $rows;
    }

    /**
     * 获取控制器及其操作的 RBAC 信息
     * @param string $classController 控制器的类名
     * @return array 如果控制器/操作没有使用 rbac 标记，则不会出现在列表中
     * @throws \ReflectionException
     */
    public function getControllerInfo(string $classController): array
    {
        $controllerRef = new \ReflectionClass($classController);
        $ctrlRbac = $this->getAnnotation($controllerRef);
        if ($ctrlRbac === false) {
            return [];
        }
        if (empty($ctrlRbac)) {
            $ctrlRbac['title'] = Router::formatName($controllerRef->getShortName(), false);
        }
        $actionsRbac = $this->getActionsAnnotation($controllerRef);
        $ctrlRbac['actions'] = $actionsRbac;
        return $ctrlRbac;
    }

    private function getAnnotationForClass($className): bool|array
    {
        $controllerRef = new \ReflectionClass($className);
        return $this->getAnnotation($controllerRef);
    }

    /**
     * 获取指定类的 rbac 信息
     * @param \ReflectionClass $ref 控制器 的反射类
     * @return array|false false 表示完全没有定义
     * @link https://docs.phalcon.io/5.0/en/annotations
     */
    private function getAnnotation(\ReflectionClass $ref): array|bool
    {
        $cDos = $this->parse->parse($ref->getDocComment());
        if (isset($cDos['rbac'])) {
            return ServicesJSON::decode($this->getDocContent($cDos['rbac']), ServicesJSON::GET_ARRAY);
        }
        return false;
    }

    /**
     * 读取指定控制器下所有 Action 的 rbac 信息
     * @param \ReflectionClass $ref 控制器反射对象
     * @return array
     * @throws \Exception
     */
    private function getActionsAnnotation(\ReflectionClass $ref): array
    {
        $methods = [];
        foreach ($ref->getMethods() as $method) {
            if ($method->isPublic() && str_ends_with($method->getName(), 'Action')) {
                $mDoc = $this->parse->parse($method->getDocComment());
                if (isset($mDoc['rbac'])) {
                    $name = substr($method->getName(), 0, -6); // Action 名称
                    $dInfo = ServicesJSON::decode($this->getDocContent($mDoc['rbac']), ServicesJSON::GET_ARRAY);
                    if (is_null($dInfo)) {
                        throw new \Exception('is rbac annotation ok?' . $mDoc['rbac']);
                    } elseif (empty($dInfo)) {
                        $dInfo['title'] = Router::formatName($name);
                    }
                    $methods[$name] = $dInfo;
                }
            }
        }
        return $methods;
    }

    /**
     * 格式化 rbac  注解信息, 将 "({title:'系统节点管理',scope:1})" 去掉左右两边的括号
     * @param string $doc
     * @return string
     */
    private function getDocContent(string $doc): string
    {
        $doc = trim($doc, ' '); // 去掉两边多余的穴格
        return str_starts_with($doc, '(')
            ? substr($doc, 1, -1)
            : $doc;
    }

}