#!/bin/bash

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

phpIni="php/extra.ini"
if [ -f "$phpIni" ]; then
    echo "skip, already has $phpIni"
else
    echo "start to copy $phpIni"
    cp php/extra.example.ini $phpIni || { echo "|__ failed"; exit 1; }
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
chown -R 1000:1000 src/storage

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

