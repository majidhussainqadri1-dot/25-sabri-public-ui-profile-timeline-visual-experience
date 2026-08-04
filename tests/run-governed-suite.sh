#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BOOTSTRAP="$ROOT/tests/plan-completion-bootstrap.php"

while IFS= read -r test_file; do
  php -d "auto_prepend_file=$BOOTSTRAP" "$test_file"
done < <(find "$ROOT/tests" -maxdepth 1 -type f -name '*.php' \
  ! -name '*bootstrap.php' \
  ! -name 'plan-completion-bootstrap.php' \
  | LC_ALL=C sort)

php "$ROOT/tools/verify-source-completion.php"
php "$ROOT/tools/verify-structure.php"
php "$ROOT/tools/verify-file06-and-components.php"
php "$ROOT/tools/verify-provider-metadata.php"
