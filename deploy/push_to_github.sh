#!/usr/bin/env bash
set -euo pipefail

REPO_URL=${1:-git@github.com:Lmaw-dev/bfcoffee.git}
BRANCH=${2:-main}

echo "Using repository: $REPO_URL"

if [ ! -d .git ]; then
  echo "No git repo found; initializing..."
  git init
fi

git remote remove origin 2>/dev/null || true
git remote add origin "$REPO_URL"

git add .
git commit -m "deploy: prepare for Render/Vercel deployment" || true
git branch -M "$BRANCH"

echo "Pushing to $REPO_URL on branch $BRANCH..."
git push -u origin "$BRANCH"

echo "Push complete. Now go to Render and Vercel to connect the repo and follow DEPLOY.md steps." 
