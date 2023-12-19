#!/bin/bash

# 此脚本用于创建 phalcon 项目，示例
# /data
#   |-- app.sh
# 执行 sh app.sh create phalcon-app
# 则会生成以下目录
# /data
#   |-- app.sh
#   |-- phalcon-app
# cd phalcon-app 然后执行其它命令

function copy_files() {
  local source="$1"
  local target="$2"
  local files="docker-compose.yaml .env nginx/default.conf php/php.ini src/config.php src.env"

  for file in $files; do
    cp "$source/$file" "$target/$file"
  done
}

origin="https://github.com/tao996"

# 当前脚本所在目录
basepath=$(pwd)

if [ -z $2 ]; then
  echo '必须指定项目名称'
  exit 1
fi

# 创建项目
if [ 'create' == $1 ]; then
  echo "prepare to create the project: $2"
  # 检查目录是否存在
  if [[ -d "${basepath}/$2" ]]; then
    echo "待创建的目录已存在"
    exit 1
  fi
  git clone ${origin}/phalcon-app $2

  # 克隆 admin/assets
  git clone ${origin}/phalcon-app-admin.git ${2}/src/app/Modules/tao
  git clone ${origin}/phalcon-app-assets.git ${2}/src/public/assets
  exit 0
fi

# 更新项目仓库
if [ 'update' == $1 ]; then
  echo "prepare to update the project: $2"

  if [ ! -d "${basepath}/$2" ]; then
    echo "待更新的目录不存在: ${basepath}/$2"
    exit 1
  fi

  cd ${origin}/phalcon-app
  git pull

  cd ${origin}/phalcon-app-tao
  git pull

  cd ${origin}/phalcon-app-assets
  git pull

  cd ${origin}
  exit 0
fi

# 备分项目配置信息
if [ 'backup' == $1 ]; then
  if [ -z $3 ]; then
    echo '必须指定备份目录'
    exit 1
  fi
  if [ ! -d "${basepath}/$2" ]; then
    echo "待备份的目录不存在: ${basepath}/$2"
    exit 1
  fi

  copy_files "$2" "$3"


  echo "备份完成"
  exit 0
fi

# 恢复备份的配置文件到指定目录
if [ 'recover' == $1 ];then
  if [ -z $3 ]; then
    echo '必须指定备份目录'
    exit 1
  fi

  if [ ! -d "${basepath}/$2" ]; then
    echo "待恢复的目录不存在: ${basepath}/$2"
    exit 1
  fi

  if [ ! -d "${basepath}/$3" ]; then
    echo "备份的目录不存在: ${basepath}/$3"
    exit 1
  fi

  copy_files "$3" "$2"

  echo "备份完成"
  exit 0
fi

echo "-----"
echo "创建项目 sh app.sh create yourName"
echo "更新项目 sh app.sh update yourName"
echo "-----"
