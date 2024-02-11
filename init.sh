#!/bin/bash

##### 脚本内容
# 将 `nginx/default.example.conf` 复制为 `nginx/default.conf` 以对 `docker nginx` 进行配置
# 将 `php/php.example.ini` 复制为 `php.ini` 以对 `docker php` 进行配置
# 将 `.env.example` 复制为 `.env`
# 将 `src/.env.example` 复制为 `.env` 以配置 `phalcon-app` 项目
#       将 `src/config/config.example.php` 复制为 `src/config/config.php` 对项目进行配置
#####

# just test in CentOs 7.6
if [ -f /bin/bash ]; then
    echo "start copy files..."
else
    echo "the deploy.sh on run in Linux System"
    exit 1
fi

dockerYaml="docker-compose.yaml"
if [ -f "$dockerYaml" ]; then
    echo "skip, already has $dockerYaml"
else
    echo "start to cope $dockerYaml"

    cp docker-compose.example.yaml $dockerYaml || { echo "|__ failed"; exit 1; }
    echo "|__ success"
fi

if [ -f ".env" ]; then
    echo "skip, already has .env"
else
    echo "start to cope .env"
    cp .env.example .env || { echo "|__ failed"; exit 1; }
    echo "|__ success"
fi

nginxConfig="nginx/default.conf"
if [ -f "$nginxConfig" ]; then
    echo "skip, already has $nginxConfig"
else
    echo "start to copy $nginxConfig"
    cp nginx/default.example.conf $nginxConfig || { echo "|__ failed"; exit 1; }
    echo "|__ success"
fi

phpIni="php/php.ini"
if [ -f "$phpIni" ]; then
    echo "skip, already has $phpIni"
else
    echo "start to copy $phpIni"
    cp php/php.example.ini $phpIni || { echo "|__ failed"; exit 1; }
    echo "|__ success"
fi

srcEnv="src/.env"
if [ -f "$srcEnv" ]; then
    echo "skip, already has $srcEnv"
else
    echo "start to copy $srcEnv"
    cp $srcEnv.example $srcEnv || { echo "|__ failed"; exit 1; }
    echo "|__ success"
fi

appConfig="src/config/config.php"
if [ -f "$appConfig" ]; then
    echo "skip, already has $appConfig"
else
    echo "start to copy $appConfig"
    cp src/config/config.example.php $appConfig || { echo "|__ failed"; exit 1; }
    echo "|__ success"
fi

# 修改日志目录权限
chmod 777 src/storage -R
chmod 777 src/public/upload

echo "---------------------"
if [ -f /usr/sbin/nginx ]; then
    echo "well done, already has install nginx"
    echo "next work, do it by yourself"
    echo -e "1. copy the \033[31m nginx/host.example.conf \033[0m  to the nginx virtual host \033[31m /etc/nginx/conf.d \033[0m"
    echo -e "2. add the \033[31m server names \033[0m to the \033[31m /etc/hosts \033[0m"
    echo "3. edit the src/config/config.php for the project"
else
    echo "may you should install nginx"
fi

