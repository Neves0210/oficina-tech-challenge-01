#!/bin/bash
set -eu
if [[ ! "$MYSQL_USER" =~ ^[a-zA-Z0-9_]+$ ]]; then
    echo 'MYSQL_USER must contain only letters, numbers and underscores.' >&2
    exit 1
fi
MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql --protocol=socket -uroot <<SQL
CREATE DATABASE IF NOT EXISTS oficina_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON oficina_testing.* TO '$MYSQL_USER'@'%';
SQL
