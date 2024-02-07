#!/bin/bash

VERSION="$1"
NO_OPT="$2"

if [[ -z $VERSION ]]; then
  echo "USAGE: $0 <version> [--no-tag]"
  echo " e.g.: $0 0.1.0"
  exit 1
fi

function fail {
  local msg="$1"
  echo "ERROR: $msg"
  exit 1
}

TAG=$(git tag | grep -c "$VERSION$")

if [[ "$TAG" -ne "0" ]]; then
  echo -n "Version $VERSION has already been tagged: "
  git tag | grep "$VERSION"
  exit 1
fi

BRANCH="stable/$VERSION"
git checkout -b "$BRANCH"
git rm -rf vendor
rm -rf vendor
rm -f composer.lock
composer install || fail "composer install failed"
find vendor/ -type f -name "*.php" -or -name "*.js" -or -name "*.css" -or -name "*.less" -or -name "*.json" \
 | grep -v '/examples/' \
 | grep -v '/example/' \
 | grep -v '/tests/' \
 | grep -v '/test/' \
 | xargs -L1 git add -f
find vendor/ -type f -name LICENSE | xargs -L1 git add -f
find public/ -type f | xargs -L1 git add -f
sed -i.bak "s/^Version:.*/Version: $VERSION/" module.info && rm -f module.info.bak
git add module.info
git add composer.lock -f
git commit -m "Version v$VERSION"

rm -rf vendor
git checkout vendor
composer validate --no-check-all --strict || fail "Composer validate failed"

if [ -z "$NO_OPT" ]; then
  git tag -a v$VERSION -m "Version v$VERSION"
  echo "Finished, tagged v$VERSION"
  echo "Now please run:"
else
  echo "Finished, but not tagged yet"
  echo "Now please run:"
  echo "git tag -s v$VERSION -m \"Version v$VERSION\""
fi

echo "git push -u origin "$BRANCH":"$BRANCH" && git push --tags"
