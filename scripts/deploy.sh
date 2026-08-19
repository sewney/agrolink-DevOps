#!/bin/bash

set -e

AWS_REGION="ap-south-1"
ECR_REGISTRY="947200279241.dkr.ecr.ap-south-1.amazonaws.com"
ECR_REPOSITORY="agrolink-web"

if [ -z "$1" ]; then
  echo "Usage: ./scripts/deploy.sh <image-tag>"
  exit 1
fi

IMAGE_TAG="$1"
export WEB_IMAGE="${ECR_REGISTRY}/${ECR_REPOSITORY}:${IMAGE_TAG}"

echo "Deploying ${WEB_IMAGE}"

# Login to ECR
aws ecr get-login-password --region "$AWS_REGION" | \
docker login --username AWS --password-stdin "$ECR_REGISTRY"

# Pull the exact image
docker compose pull web

# Validate Compose
docker compose config --quiet

# Update containers without rebuilding
docker compose up -d --no-build

# Verify database health
DB_HEALTH=$(docker inspect --format='{{.State.Health.Status}}' agrolink-db)

if [ "$DB_HEALTH" != "healthy" ]; then
  echo "Database is not healthy."
  exit 1
fi

# Verify application response
if ! curl -fsS http://localhost/ > /dev/null; then
  echo "Application check failed."
  exit 1
fi

echo "Deployment successful: ${IMAGE_TAG}"
