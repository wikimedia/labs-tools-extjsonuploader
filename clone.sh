#!/bin/sh
#set -x
#echo start
#date
cd /data/project/extjsonuploader/allext/extensions
git fetch --quiet --depth 1
git reset --quiet --hard origin/master
git submodule --quiet update --init --depth 1
cd ../..
cd src
php main.php
#date
#echo done
