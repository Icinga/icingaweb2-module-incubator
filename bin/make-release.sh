#!/bin/bash

VERSION="$1"

if [[ -z $VERSION ]]; then
  echo "USAGE: $0 <version>"
  echo " e.g.: $0 0.1.0"
  exit 1
fi

function fail {
  local msg="$1"
  echo "ERROR: $msg"
  exit 1
}

TAG=$(git tag | grep -c "$VERSION")

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
find vendor/ -type f -name "*.php" \
 | grep -v '/examples/' \
 | grep -v '/example/' \
 | grep -v '/tests/' \
 | grep -v '/test/' \
 | xargs -L1 git add -f
find vendor/ -type f -name LICENSE | xargs -L1 git add -f
find vendor/ -type f -name '*.json' | xargs -L1 git add -f
sed -i.bak "s/^Version:.*/Version: v$VERSION/" module.info && rm -f module.info.bak
git add module.info
git add composer.lock -f
git commit -m "Version v$VERSION"

rm -rf vendor
git checkout vendor
composer validate --no-check-all --strict || fail "Composer validate failed"

git tag -a v$VERSION -m "Version v$VERSION"
echo "Finished, tagged v$VERSION"
echo "Now please run:"
echo "git push origin "$BRANCH":"$BRANCH" && git push --tags"
