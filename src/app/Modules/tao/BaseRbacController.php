<?php

namespace App\Modules\tao;

use App\Modules\tao\Services\LoginUser;
use App\Modules\tao\Services\RoleService;
use Phax\Foundation\Router;
use Phax\Mvc\Request;
use Phax\Mvc\Response;
use Phax\Support\Exception\BlankException;

/**
 * 控制器访问权限判断
 */
class BaseRbacController extends BaseResponseController
{

    /**
     * 当前登录的用户
     * @var LoginUser
     */
    protected LoginUser $loginUser;


    /**
     * 超级用户能够访问的节点，使用 * 表示全部节点; <br>
     * 优先级：superAdminActions < openActions
     * @var array|string
     */
    protected array|string $superAdminActions = [];

    /**
     * 登录用户能够访问的节点；全部节点则使用 * 表示; <br>
     * 优先级：userActions < superAdminActions < openActions
     * @var array|string
     */
    protected array|string $userActions = [];
    /**
     * 公开的节点，使用 * 表示全部节点; <br>
     * 优先级：openActions > superAdminActions > userActions > otherActionRoles
     * @var array|string ['a1','a2'] 或者 a1,a2
     */
    protected array|string $openActions = [];

    /**
     * 其它节点所能够访问的角色
     * @var array
     */
    protected array $otherActionRoles = [];

    /**
     * @var array （白名单）当前控制器允许访问的 action，如果设置，则只有 enableActions 中的 action 才允许访问
     */
    public array $enableActions = [];
    /**
     * @var array (黑名单)当前控制器取消的操作（不是每个控制器都有 add/edit/delete）
     */
    public array $disableActions = [];

    /**
     * 是否禁用 add/edit/modify/delete 操作
     * @var bool
     */
    public bool $disableUpdateActions = false;

    protected function isLogin(): bool
    {
        return $this->tryGetLoginUser()->isLogin();
    }

    /**
     * 尝试获取登录用户的信息
     * @return LoginUser
     */
    public function tryGetLoginUser(): LoginUser
    {
        if (empty($this->loginUser)) {
            $this->loginUser = LoginUser::getInstance();
        }
        return $this->loginUser;
    }

    /**
     * 必须能够获取当前用户的信息
     * @return LoginUser
     * @throws \Exception
     */
    public function mustGetLoginUser(): LoginUser
    {
        if (!$this->tryGetLoginUser()->isLogin()) {
            throw new \Exception('用户未登录');
        }
        return $this->loginUser;
    }

    /**
     * @throws \Exception
     */
    protected function rbacInitialize(): void
    {
        // 开放接口，默认关闭修改节点
        if ($this->openActions == '*' && !$this->disableUpdateActions) {
            $this->disableUpdateActions = true;
        }

        $action = Router::formatNodeName($this->router->getActionName());
        if ($this->enableActions && !in_array($action, $this->enableActions)) {
            throw new \Exception('not in allow enableActions');
        }
        if ($this->disableActions && in_array($action, $this->disableActions)) {
            throw new \Exception('in not allow disableActions');
        }
        if ($this->disableUpdateActions && in_array($action, ['add', 'edit', 'modify', 'delete'])) {
            if (in_array($action, $this->enableActions)) {
                // allow action
            } else {
                throw new \Exception('not allow disableUpdateActions');
            }
        }

        if ($this->isOpenAction()) {
            return;
        }

        // 非公共节点都需要登录
        if (!$this->isLogin()) {
            if (Request::isApiRequest()) {
                $this->error('您还没有登录', [], 401);
            } else {
                echo Response::simpleView(self::getBaseViewDir('redirect.phtml'), [
                    'msg' => '您还没有登录，前往登录?',
                    'url' => url('tao/auth/index'),
                ]);
            }
            throw new BlankException();
        }

        if ($this->loginUser->isSuperAdmin()) {
            return;
        }
// 超级管理员节点
        if ($this->isSuperAdminAction()) {
            if (!$this->tryGetLoginUser()->isSuperAdmin()) {
                $this->accessDenyResponse('非超级管理员，无权访问');
            }
            return;
        }

        if ($this->isUserAction()) {
            return;
        }

        // 节点检查
        $auth = $this->loginUser->getAuth();

        if ($this->otherActionRoles) {
            $roleIds = RoleService::getIds($this->otherActionRoles);
            if ($auth->inRoles($roleIds)) {
                return;
            } else {
                $this->accessDenyResponse('没有访问的权限');
            }
        }

        if (!$auth->access(Router::getNode())) {
            $this->accessDenyResponse();
        }
    }

    protected function isSuperAdminAction(): bool
    {
        return '*' == $this->superAdminActions
            || in_array(
                $this->router->getActionName(),
                is_array($this->superAdminActions) ? $this->superAdminActions : explode(',', $this->superAdminActions)
            );
    }

    protected function isUserAction(): bool
    {
        return '*' == $this->userActions
            || in_array($this->router->getActionName(), is_array($this->userActions) ? $this->userActions : explode(',', $this->userActions));
    }

    protected function isOpenAction(): bool
    {
        return '*' == $this->openActions
            || in_array($this->router->getActionName(), is_array($this->openActions) ? $this->openActions : explode(',', $this->openActions));
    }

    private function accessDenyResponse($msg = '')
    {
        if ('' == $msg) {
            $msg = '没有访问的权限';
        }
        if (Request::isApiRequest()) {
            $this->error($msg, [], 403);
        } else {
            echo Response::simpleView(self::getBaseViewDir('redirect.phtml'), [
                'msg' => $msg,
                'url' => 'close',
            ]);
        }
        throw new BlankException();
    }

    /**
     * 视图的时候，会自动调用 tryGetLoginUser
     * @param mixed $data
     * @throws \Exception
     */
    protected function beforeViewResponse(mixed &$data): void
    {

        $this->tryGetLoginUser();
        $this->addViewData('demo', $this->isDemo)
            ->addViewData('user', null);

        if ($this->isLogin()) {
            $this->addViewData('user', $this->loginUser->user());
        }
        parent::beforeViewResponse($data);
    }
}