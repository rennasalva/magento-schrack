#!/bin/bash

function show_help {
    echo "./pollereur.sh [-d] [-v] [-r] [-t timestamp_file] [-c config_file] [-s script_file] [-p watched_path]"
    echo "      -a              append expanded output: don't pack files, just append them to the output file"
    echo "      -v              verbosely output whether changes were detected"
    echo "      -r              (re)create config_file"
    echo
    echo "      -c              use config_file to save the js/css files (default: $config_file"
    echo "      -i              interval between polls in seconds (default: $interval)"
    echo "      -p              path to use as root path for finding .js/.css files (default: $watched_path)"
    echo "      -s              script file to call if changes are detected (default: $script_file)"
    echo "      -t              use timestamp_file to save the current timestamp (default: $timestamp_file)"
    exit
}

function create_files_conf {
    echo "Creating config file: $config_file"

    find $watched_path -name '*.js' ! -name 'allPacked*'  ! -name '*\ *' > $config_file
    find $watched_path -name '*.css' ! -name 'allPacked*'  ! -name '*\ *' >> $config_file
    find $watched_path -name '*.scss' ! -name 'allPacked*'  ! -name '*\ *' >> $config_file
}

function run_script_file {
    if [ "$append" = "1" ]; then
        $script_file -a
    else
        $script_file
    fi
}


# A POSIX variable
OPTIND=1         # Reset in case getopts has been used previously in the shell.

verbose=0
append=0
recreate_config_file=0
config_file=files.conf
script_file=./packAllCssAndJs.sh
timestamp_file=timestamp.file
watched_path=../../
interval=10

while getopts "h?avrt:c:s:p:i:" opt; do
    case "$opt" in
    h|\?)
        show_help
        exit 0
        ;;
    v)  verbose=1
        ;;
    a)  append=1
        ;;
    t)  timestamp_file=$OPTARG
        ;;
    c)  config_file=$OPTARG
        ;;
    s)  script_file=$OPTARG
        ;;
    p)  watched_path=$OPTARG
        ;;
    r)  recreate_config_file=1
        ;;
    i)  interval=$OPTARG
        ;;
    esac
done

shift $((OPTIND-1))

[ "$1" = "--" ] && shift

echo "verbose=$verbose, timestamp_file='$timestamp_file', config_file='$config_file', script_file='$script_file', watched_path='$watched_path'"

[ "$verbose" = "1" ] && echo "initial packing..."

run_script_file

[ "$recreate_config_file" = "1" ] && create_files_conf

touch $timestamp_file

[ "$verbose" = "1" ] && echo "polling for changes..."

while true; do
    if [ "$(cat files.conf |while read i; do find $i -newer timestamp.file; done|wc -l)" != "0" ]; then
        [ "$verbose" = "1" ] && echo `date` " -- changes detected"
        run_script_file
        [ "$verbose" = "1" ] && echo `date` " -- done running scripts"
        sleep $interval
        touch $timestamp_file
    else 
        [ "$verbose" = "1" ] && echo `date` " -- no changes"
        sleep $interval
    fi
done
