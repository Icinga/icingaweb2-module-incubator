#!/bin/bash

VERSION="$1"

if [ -z $VERSION ]; then
  echo "USAGE: $0 <version>"
  echo " e.g.: $0 0.1.0"
  exit 1
fi

TAG=$(git tag | grep -c "$VERSION")

if [ "$TAG" -ne "0" ]; then
  echo -n "Version $VERSION has already been tagged: "
  git tag | grep "$VERSION"
  exit 1
fi

BRANCH="stable/$VERSION"
git checkout -b "$BRANCH"
git rm -rf vendor
rm -rf vendor
rm composer.lock
composer install
find vendor/ -type f -name "*.php" \
 | grep -v '/examples/' \
 | grep -v '/example/' \
 | grep -v '/tests/' \
 | grep -v '/test/' \
 | xargs -l git add -f
find vendor/ -type f -name LICENSE | xargs -l git add -f
git commit -m "Version v$VERSION"

rm -f composer.lock
rm -rf vendor
git checkout vendor

git tag -a v$VERSION -m "Version v$VERSION"
echo "Finished, tagged v$VERSION"
echo "Now please run:"
echo "git push origin "$BRANCH":"$BRANCH" && git push --tags"
