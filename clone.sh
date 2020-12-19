#!/bin/sh

THISDIR=$(cd "$(dirname "$0")" && pwd -P)
EXTDIR=$(grep 'extensionDir' "$THISDIR/config.php"  | cut -d\' -f4)

if [ ! -d "$EXTDIR" ]; then
    echo "ERROR: extensions directory not found. Please run:"
    echo "git clone http://gerrit.wikimedia.org/r/p/mediawiki/extensions.git $EXTDIR"
    exit 1
fi;

cd "$EXTDIR" || { echo "Unable to change to $EXTDIR"; exit 1; }
git fetch --quiet --depth 1
git reset --quiet --hard origin/master
git submodule --quiet update --init --depth 1
cd "$THISDIR" || { echo "Unable to change back to $THISDIR"; exit 1; }
php run.php
