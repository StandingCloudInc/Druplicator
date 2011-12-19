#!/bin/bash - 
#===============================================================================
#
#          FILE:  druplicator.sh
# 
#         USAGE:  ./druplicator.sh 
# 
#   DESCRIPTION:  
# 
#       OPTIONS:  ---
#  REQUIREMENTS:  ---
#          BUGS:  ---
#         NOTES:  ---
#        AUTHOR: Nick Henry (NSH), nickh@standingcloud.com
#       COMPANY: Standing Cloud, Inc.
#       CREATED: 11/08/2011 08:59:35 AM MST
#
#       LICENSE:  You may not use, copy, or modify this software without the
#                 permission of Standing Cloud, Inc. Contact us at
#                 support@standingcloud.com to request permission.
#===============================================================================

#set -o nounset                                 # Treat unset variables as an error

#===============================================================================
#   GLOBAL DECLARATIONS
#===============================================================================
declare -rx SCRIPT=${0##*/}                     # the name of this script

ARCHIVE=${1}                                    # the archive if passed
ARCHIVE=${ARCHIVE:-"${HOME}/tmp/tarArchive.tar.bz2"}  # the archive if not passed
EXTRACT_DIR=$(tar tjf ${ARCHIVE} 2>/dev/null | head -1)
TMPFILE=$( mktemp /tmp/htdocs.XXXXXXXXXX ) || exit 1 ; rm --force ${TMPFILE}
WWW_CODE_DIR="${HOME}/htdocs"
SC_SYSUSER='sc-sysuser'
SYSTEM_USER="$(whoami)"
SQL_FILE='mysqlDatabaseDump.sql'
BACKUP_DIR_NAME='standing_clouds_druplicator'
POSSIBLE_LOCATIONS_FOR_SQL_FILE="${WWW_CODE_DIR}/sites/default/files/${BACKUP_DIR_NAME} ${WWW_CODE_DIR}/${BACKUP_DIR_NAME} ${HOME}/tmp/${BACKUP_DIR_NAME}"


#===  FUNCTION  ================================================================
#          NAME:  fixPermissions
#   DESCRIPTION:  fix permissions using acls for htdocs
#    PARAMETERS:  
#       RETURNS:  
#===============================================================================
fixPermissions ()
{
	# Reset all permissions for this user
	find ${WWW_CODE_DIR} -depth -exec setfacl --remove-all --remove-default {} \;

	local existingusers='www-data'

	# Figure out which users exist that need access
	local thisuser=''
	local existingusers=''
	for thisuser in www-data ${SC_SYSUSER}
	do
		if $(id -u $thisuser >/dev/null 2>&1); then
			existingusers="${existingusers}${existingusers:+ }${thisuser}"
		fi
	done

	#-------------------------------------------------------------------------------
	#  GIVE OTHER USERS RW ACCESS (www-data for example)
	#-------------------------------------------------------------------------------
	# Set the 'set groupid' bit
	find ${WWW_CODE_DIR} -type d -exec chmod g+s {} \;

	# Setting acls for the user
	find ${WWW_CODE_DIR} -type f -exec setfacl -m u:${SYSTEM_USER}:rw {} \;
	find ${WWW_CODE_DIR} -type d -exec setfacl -m u:${SYSTEM_USER}:rwx {} \;
	setfacl -R -d -m u:${SYSTEM_USER}:rwx ${WWW_CODE_DIR}

	local thisuser=''
	for thisuser in $existingusers
	do
		# Set the default group as www-data
		if [ "$thisuser" = "www-data" ]; then
			: # :TODO:11/03/2011 04:53:20 PM MDT:NSH: Need to be root or part of that group to change a file/directory to that group... Can't do this at this time...
#			chgrp -R www-data ${WWW_CODE_DIR}
		fi

		# Setting acls for $thisuser
		find ${WWW_CODE_DIR} -type f -exec setfacl -m g:${thisuser}:rw {} \;
		find ${WWW_CODE_DIR} -type d -exec setfacl -m g:${thisuser}:rwx {} \;
		setfacl -R -d -m u:${thisuser}:rwx ${WWW_CODE_DIR}
	done


	#-------------------------------------------------------------------------------
	#  MAKE DRUPAL HAPPY ABOUT PERMISSIONS
	#-------------------------------------------------------------------------------
	chmod g-w ${WWW_CODE_DIR}/sites/default ${WWW_CODE_DIR}/sites/default/settings.php
}	# ----------  end of SYSTEM_USERPermissions  ----------


#===============================================================================
#   SANITY CHECKS
#===============================================================================
if [ -z "$BASH" ] ; then
	printf "$SCRIPT:$LINENO: run this script with the BASH shell\n" >&2
	exit 192
fi

if [ ! -f "$ARCHIVE" ] ; then
	printf "$SCRIPT:$LINENO: archive does not exist\n" >&2
	exit 192
fi

if [ -z "$EXTRACT_DIR" ] ; then
	printf "$SCRIPT:$LINENO: could not find 'extract dir'\n" >&2
	exit 192
fi

if [ "$SYSTEM_USER" == "root" ] ; then
	printf "$SCRIPT:$LINENO: run this script as the system user, not as root\n" >&2
	exit 192
fi

if [ "$SYSTEM_USER" == "$SC_SYSUSER" ] ; then
	printf "$SCRIPT:$LINENO: run this script as the system user, not as ${SC_SYSUSER}\n" >&2
	exit 192
fi


#===============================================================================
#   MAIN SCRIPT
#===============================================================================
# Get the database information
cd ${WWW_CODE_DIR}
	MY_HOST=$(drush status --show-passwords | fgrep 'Database hostname' | awk '{ print $NF }')
	MY_USER=$(drush status --show-passwords | fgrep 'Database username' | awk '{ print $NF }')
	MY_PASS=$(drush status --show-passwords | fgrep 'Database password' | awk '{ print $NF }')
	MY_NAME=$(drush status --show-passwords | fgrep 'Database name'     | awk '{ print $NF }')
cd ${OLDPWD}

# Move 'htdocs' to 'TMPFILE'
mv ${WWW_CODE_DIR} ${TMPFILE}

# Extract the archive to the home directory
tar --directory="${HOME}" --overwrite --no-same-permissions -xjf ${ARCHIVE}

# If the extracted directory isn't 'htdocs', rename it to htdocs
[ "${EXTRACT_DIR%/}" != "htdocs" ] && mv ${HOME}/${EXTRACT_DIR%/} ${WWW_CODE_DIR}

# Find the sql file
LOCATION_OF_SQL_FILE=''
for dir in $POSSIBLE_LOCATIONS_FOR_SQL_FILE; do
	if [ -f "${dir}/${SQL_FILE}" ]; then
		LOCATION_OF_SQL_FILE="${dir}/${SQL_FILE}"
		break
	fi
done
if [ -z "$LOCATION_OF_SQL_FILE" ] ; then
	printf "$SCRIPT:$LINENO: could not find a sql file to restore\n" >&2
	exit 192
fi

# Recreate the database and then restore the database from the archive
sed -i -e "/^CREATE DATABASE/d" -e "/^USE/d" ${LOCATION_OF_SQL_FILE}
mysql --host="${MY_HOST}" --user="${MY_USER}" --password="${MY_PASS}" --database="${MY_NAME}" --execute="DROP DATABASE IF EXISTS ${MY_NAME}; CREATE DATABASE ${MY_NAME};"
mysql --host="${MY_HOST}" --user="${MY_USER}" --password="${MY_PASS}" --database="${MY_NAME}" < ${LOCATION_OF_SQL_FILE}

# Remove the database from the extracted archive
rm -f ${LOCATION_OF_SQL_FILE}
rmdir ${LOCATION_OF_SQL_FILE%/*}


#-------------------------------------------------------------------------------
#  RECONNECT THEIR APPLICATION TO THE DATABASE
#-------------------------------------------------------------------------------
cp -a ${WWW_CODE_DIR}/sites/default/settings.php ${HOME}/tmp/
problemWithInlineChanges='no'
sed -i "s/^\(\s*'database'\s*=>\s*'\)[^']*\(.*\)$/\1${MY_NAME}\2/" ${WWW_CODE_DIR}/sites/default/settings.php || problemWithInlineChanges='yes'
sed -i "s/^\(\s*'username'\s*=>\s*'\)[^']*\(.*\)$/\1${MY_USER}\2/" ${WWW_CODE_DIR}/sites/default/settings.php || problemWithInlineChanges='yes'
sed -i "s/^\(\s*'password'\s*=>\s*'\)[^']*\(.*\)$/\1${MY_PASS}\2/" ${WWW_CODE_DIR}/sites/default/settings.php || problemWithInlineChanges='yes'
sed -i "s/^\(\s*'host'\s*=>\s*'\)[^']*\(.*\)$/\1${MY_HOST}\2/"     ${WWW_CODE_DIR}/sites/default/settings.php || problemWithInlineChanges='yes'

if [ "${problemWithInlineChanges}" == 'yes' ]; then
	echo -e "\n\n\e[1;31mNOTICE:\033[0m Moving your 'sites/default/settings.php' to '${HOME}/tmp/settings.php' and replacing with the original settings.php (So that your application will be able to connect to the database)\n\n"
	cp -a /tmp/htdocs/sites/default/settings.php ${WWW_CODE_DIR}/sites/default/settings.php
fi


#-------------------------------------------------------------------------------
#  FIX PERMISSIONS
#-------------------------------------------------------------------------------
fixPermissions


#===============================================================================
#   STATISTICS / CLEANUP
#===============================================================================
exit 0
