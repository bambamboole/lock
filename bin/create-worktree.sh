#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 ]]; then
    echo "Usage: bin/create-worktree.sh <name> [branch]" >&2
    exit 1
fi

name="$1"
branch="${2:-$1}"

if [[ ! "$name" =~ ^[a-zA-Z0-9._-]+$ ]]; then
    echo "error: <name> may only contain letters, numbers, dots, dashes, underscores" >&2
    exit 1
fi

repo_root="$(git rev-parse --show-toplevel)"
worktree_dir="$(dirname "$repo_root")/lock-$name"
site_name="lock-$name"

if [[ -e "$worktree_dir" ]]; then
    echo "error: $worktree_dir already exists" >&2
    exit 1
fi

git -C "$repo_root" worktree add "$worktree_dir" -b "$branch" main
cd "$worktree_dir"

cp .env.example .env

# Herd serves the worktree under its own hostname, so the site has to exist and
# APP_URL has to match it before the frontend is built — Vite bakes the VITE_*
# values into the bundle.
herd link "$site_name"
herd secure "$site_name"
sed -i.bak "s#^APP_URL=.*#APP_URL=https://$site_name.test#" .env && rm .env.bak

touch database/database.sqlite

composer install
php artisan key:generate --ansi
php artisan migrate --force --ansi
php artisan migrate --path=database/data-migrations --force --ansi
php artisan oidc:rotate-keys --if-missing --ansi
php artisan permission:sync --ansi
php artisan app:bootstrap --ansi

# `npm ci` and not `npm install`: install rewrites package-lock.json with the
# platform's optional deps, which would leave every fresh worktree dirty and
# trip delete-worktree.sh's uncommitted-changes guard.
npm ci
npm run build

echo
echo "Worktree ready: $worktree_dir"
echo "Serving at:     https://$site_name.test"
