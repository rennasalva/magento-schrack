#!/bin/bash

BASEDIR=$(dirname $0)
echo "packAllCssAndJs: using basedir $BASEDIR"

if [ "$1" = "-a" ]; then # append expanded output
    php "$BASEDIR/packAllCsses.php" -a
    php "$BASEDIR/packAllJavascripts.php" -a
else
    php "$BASEDIR/packAllCsses.php"
    php "$BASEDIR/packAllJavascripts.php"
fi
