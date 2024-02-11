<?php

namespace app\Modules\tao\Controllers\admin;

use app\Modules\tao\BaseController;
use app\Modules\tao\Helper\RbacAnnotation;
use app\Modules\tao\Models\SystemNode;
use app\Modules\tao\Models\SystemRoleNode;
use app\Modules\tao\Services\NodeService;
use Phax\Db\QueryBuilder;

/**
 * @rbac ({title:'系统节点管理'})
 * @property SystemNode $model
 */
class NodeController extends BaseController
{

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new SystemNode();
    }

    protected array $allowModifyFields = [
        'title'
    ];

    private function formatRows(array $rows)
    {
        $list = [];
        $frontend = [
            'type' => SystemNode::TYPE_MODULE,
            'node' => 'FRONTEND', 'title' => '前端应用', 'is_auth' => 0,
            'isParent' => true, 'children' => [], 'pid' => 0,
        ];
        $frontendChildrenIndex = -1;
        foreach ($rows as $firstItem) {
            if ($firstItem['module'] == '') { // 前端
                if ($firstItem['type'] == SystemNode::TYPE_CONTROLLER) {
                    $frontend['children'][] = array_merge($firstItem, [
                        'isParent' => true, 'children' => [], 'pid' => 0
                    ]);
                    $frontendChildrenIndex += 1;
                } elseif ($firstItem['type'] == SystemNode::TYPE_ACTION) {
                    $parentCtrl = $frontend['children'][$frontendChildrenIndex];
                    $frontend['children'][$frontendChildrenIndex]['children'][] =
                        array_merge($firstItem, ['pid' => $parentCtrl['id'] ?? 0]);
                }
                continue;
            }

            if ($firstItem['type'] == SystemNode::TYPE_MODULE) {
                $firstItem['isParent'] = true;
                $firstItem['children'] = [];
                $firstItem['pid'] = 0;

                foreach ($rows as $secondItem) {
                    if ($secondItem['type'] == SystemNode::TYPE_CONTROLLER && $secondItem['module'] == $firstItem['module']) {

                        $secondItem['isParent'] = true;
                        $secondItem['pid'] = $firstItem['id'] ?? 0;
                        $secondItem['children'] = [];

                        foreach ($rows as $thirdItem) {
                            if ($thirdItem['type'] == SystemNode::TYPE_ACTION
                                && str_starts_with($thirdItem['node'], $secondItem['node'])) {
                                $thirdItem['pid'] = $secondItem['id'] ?? 0;
                                $secondItem['children'][] = $thirdItem;
                            }
                        }

                        $firstItem['children'][] = $secondItem;
                    }
                }

                $list[] = $firstItem;
            }
        }
        array_unshift($list, $frontend);
        return $list;
    }

    protected function indexActionGetResult(int $count, QueryBuilder $queryBuilder): array
    {
        $rows = $this->getSystemNodes($queryBuilder);
        return $this->formatRows($rows);
    }

    private function getSystemNodes(QueryBuilder $queryBuilder = null): array
    {
        if (is_null($queryBuilder)) {
            $queryBuilder = SystemNode::queryBuilder();
        }
        return $queryBuilder
            ->columns(['id', 'module', 'node', 'title', 'type', 'is_auth', 'ac'])
            ->disabledPagination()
//            ->order('type asc,id asc')
            ->find();
    }

    /**
     * @rbac({title:'更新节点'})
     */
    public function reloadAction($update = false)
    {
        $aa = new RbacAnnotation();
        $nodes = $aa->autoProduct()->loadNodes(); // 新的分析节点

        $dbNodes = $this->getSystemNodes(); // 原节点
        $rows = NodeService::compare($dbNodes, $nodes);


        if ($update) {
            // append, update, delete
            $deleteIds = []; // 删除的节点
            foreach ($rows['delete'] as $row) {
                $deleteIds[] = $row['id'];
            }
            $pdo = pdo();
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('UPDATE ' . SystemNode::getObject()->getSource() . ' SET title=:title, is_auth=:is_auth, ac=2 WHERE id=:id');
                foreach ($rows['update'] as $row) {
                    $stmt->execute([
                        'title' => $row['title'], 'is_auth' => $row['is_auth'], 'id' => $row['id']
                    ]);
                }
                // 添加新的记录
                foreach ($rows['append'] as $index => $row) {
                    $rows['append'][$index]['ac'] = SystemNode::AC_INSERT;
                }
                SystemNode::batchInsert($rows['append'], [], false);

                // 移除旧的记录
                if ($deleteIds) {
                    $sql = 'DELETE FROM ' . SystemNode::getObject()->getSource() . ' WHERE id IN (' . join(',', $deleteIds) . ')';
                    $pdo->exec($sql);

                    // 移除角色绑定
                    $roleNodeSQL = 'DELETE FROM ' . SystemRoleNode::getObject()->getSource() . ' WHERE node_id IN (' . join(',', $deleteIds) . ')';
                    $pdo->exec($roleNodeSQL);
                }

                $pdo->commit();
            } catch (\Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

//            SystemNode::truncate(true);
//            SystemNode::batchInsert($nodes);
            $qb = SystemNode::queryBuilder();
            $rows = $this->indexActionGetResult(0, $qb);
        } else {
            foreach ($rows['append'] as $row) {
                foreach ($nodes as $index => $node) {
                    if (NodeService::sameNode($row, $node)) {
                        $nodes[$index]['ac'] = SystemNode::AC_INSERT;
                        break;
                    }
                }
            }
            foreach ($rows['update'] as $row) {
                foreach ($nodes as $index => $node) {
                    if (NodeService::sameNode($row, $node)) {
                        $nodes[$index]['ac'] = SystemNode::AC_UPDATE;
                        break;
                    }
                }
            }
            $rows = $this->formatRows($nodes);
        }
        return $this->successPagination(count($rows), $rows);
    }
}