<?php

namespace app\Modules\tao\Services;

class NodeService
{

    public static function compare(array $dbNodes, array $newNodes): array
    {


        foreach ($dbNodes as $hasIndex => $dbNode) {
            foreach ($newNodes as $index => $node) {
                if (isset($node['find'])) {
                    continue;
                }

                if (self::sameNode($dbNode, $node)) {
                    $newNodes[$index]['find'] = 1;
                    $dbNodes[$hasIndex]['action'] = 'find';

                    if ($dbNode['title'] != $node['title'] || $dbNode['is_auth'] != $node['is_auth']) {
                        $dbNodes[$hasIndex]['title'] = $node['title'];
                        $dbNodes[$hasIndex]['is_auth'] = $node['is_auth'];
                        $dbNodes[$hasIndex]['action'] = 'update'; // 需要修改
                    }
                    break;
                }
            }
        }
        $rows = [];
        $rows['delete'] = array_filter($dbNodes, function ($row) {
            return !isset($row['action']);
        });
        $rows['update'] = array_filter($dbNodes, function ($row) {
            return isset($row['action']) && $row['action'] == 'update';
        });

        $rows['append'] = array_filter($newNodes, function ($row) {
            return !isset($row['find']);
        });

        return $rows;
    }

    public static function sameNode($node1, $node2): bool
    {
        return $node1['module'] == $node2['module']
            && $node1['node'] == $node2['node']
            && $node1['type'] == $node2['type'];
    }
}