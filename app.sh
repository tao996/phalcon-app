#!/bin/bash

origin="git@github.com:tao996"

# 当前脚本所在目录
basepath=`pwd`

if [ 'create' == $1 ]; then

  echo "prepare to create the project: $2"
  # 克隆主目录
  if [ -z $2 ]; then
    echo '必须指定要创建的目录'
    exit 1
  else
    # 检查目录是否存在
    if [[ -d "${basepath}/$2" ]]; then
      echo "待创建的目录已存在"
      exit 1
    fi
    echo "git clone ${origin}/phalcon-app $2"
  fi

  # 克隆 admin/assets
  echo "git clone ${origin}/phalcon-app-admin.git ${2}/src/app/Modules/tao"
  echo "git clone ${origin}/phalcon-app-assets.git ${2}/src/public/assets"
  exit 0
fi

if [ 'update' == $1 ]; then
  echo "prepare to update the project: $2"
  if [ -z $2 ]; then
    echo '必须指定要更新的项目名称'
    exit 1
  fi
  if [ ! -d "${basepath}/$2" ]; then
    echo "待更新的目录不存在: ${basepath}/$2"
    exit 1
  fi
  echo "cd ${origin}/phalcon-app"
  echo "git pull"
  echo "cd ${origin}/phalcon-app-tao"
  echo "git pull"
  echo "cd ${origin}/phalcon-app-assets"
  echo "git pull"
  echo "cd ${origin}"
  exit 0
fi

echo "-----"
echo "创建项目 sh app.sh create yourName"
echo "更新项目 sh app.sh update yourName"
echo "-----"
