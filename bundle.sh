#!/usr/bin/env bash

set -euo pipefail

pwd=$(dirname "$0")
output_file=${pwd}/dist/pkg_externallogin.zip

php "${pwd}/bundle.php" "${pwd}/src/administrator/modules/mod_externallogin_admin/mod_externallogin_admin.xml" "${pwd}/src/packages/mod_externallogin_admin.zip"
php "${pwd}/bundle.php" "${pwd}/src/com_externallogin.xml" "${pwd}/src/packages/com_externallogin.zip"
php "${pwd}/bundle.php" "${pwd}/src/modules/mod_externallogin_site/mod_externallogin_site.xml" "${pwd}/src/packages/mod_externallogin_site.zip"
php "${pwd}/bundle.php" "${pwd}/src/plugins/authentication/externallogin/externallogin.xml" "${pwd}/src/packages/plg_authentication_externallogin.zip"
php "${pwd}/bundle.php" "${pwd}/src/plugins/system/externallogin/externallogin.xml" "${pwd}/src/packages/plg_system_externallogin.zip"
php "${pwd}/bundle.php" "${pwd}/src/plugins/system/caslogin/caslogin.xml" "${pwd}/src/packages/plg_system_caslogin.zip"
php "${pwd}/bundle.php" "${pwd}/src/plugins/user/cbexternallogin/cbexternallogin.xml" "${pwd}/src/packages/plg_user_cbexternallogin.zip"
php "${pwd}/bundle.php" "${pwd}/src/pkg_externallogin.xml" "${output_file}"

rm -rf "${pwd}/src/packages"

echo ""
echo "Created ${output_file}"