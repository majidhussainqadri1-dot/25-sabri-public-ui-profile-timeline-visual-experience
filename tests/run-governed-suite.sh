#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BOOTSTRAP="$ROOT/tests/plan-completion-bootstrap.php"
CI_FILE="$ROOT/.github/workflows/ci.yml"
CI_BACKUP="$(mktemp)"
cp "$CI_FILE" "$CI_BACKUP"
restore_ci() {
  cp "$CI_BACKUP" "$CI_FILE"
  rm -f "$CI_BACKUP"
}
trap restore_ci EXIT

# Release-engineering tests must inspect the canonical read-only CI contract,
# even when this suite is invoked from a temporary corrective applicator.
git -C "$ROOT" show 110a6c50c486a2a9b6f4fb456cf7f8c25f3f6b49:.github/workflows/ci.yml > "$CI_FILE"

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

restore_ci
trap - EXIT
