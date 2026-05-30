#!/bin/bash
set -euo pipefail

BUCKET="${AWS_BUCKET:-fitness-local}"

awslocal s3 mb "s3://${BUCKET}" 2>/dev/null || true
