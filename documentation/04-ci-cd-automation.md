# 04. CI/CD Automation

## 1. Overview

This phase focuses on automating the AgroLink deployment process using GitHub Actions, Docker, Amazon ECR, and EC2.

The goal is to build the Docker image once in CI, store the versioned image in Amazon ECR, and deploy that same image to EC2 instead of rebuilding it again on the server.

---

## 2. Objectives

- Automate validation and Docker image building using GitHub Actions.
- Store versioned AgroLink web images in Amazon ECR.
- Deploy the tested image to EC2.
- Use `deploy.sh` to keep the EC2 deployment process consistent.
- Verify the deployed containers, database health, and application response.
- Stop deployment when validation or deployment checks fail.

---

## 3. Final Architecture

```text
                         Developer
                             │
                          git push
                             │
                             ▼
                    ┌─────────────────┐
                    │     GitHub      │
                    │   Repository    │
                    └────────┬────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │ GitHub Actions  │
                    │                 │
                    │  Validation     │
                    │       ↓         │
                    │ Docker Build    │
                    │       ↓         │
                    │ Push to ECR     │
                    └──────┬─────┬────┘
                           │     │
                Store image│     │ SSH deploy
                           ▼     ▼
                    ┌─────────┐  ┌───────────────┐
                    │Amazon ECR│  │      EC2      │
                    │         │  │               │
                    │ SHA-tag │◄─│   deploy.sh   │
                    │  image  │  │       ↓       │
                    └─────────┘  │ Docker Compose│
                                 │   ┌────┴────┐ │
                                 │   ▼         ▼ │
                                 │  Web      MySQL│
                                 │            │  │
                                 │        Health │
                                 │         Check │
                                 └───────────────┘
```

Amazon ECR stores the Docker image. GitHub Actions will later trigger the deployment, and EC2 will pull and run the required image through Docker Compose.

---

## 4. Steps Followed

### Step 1 — Create the Amazon ECR Repository

A private ECR repository was created for the AgroLink web image.

![Created ECR](screenshots/04-/making_ecr_repo.png)

The plan is to tag images using the Git commit SHA so each deployed image can be traced back to a specific commit.

Example:

```text
947200279241.dkr.ecr.ap-south-1.amazonaws.com/agrolink-web:<commit-sha>
```
---

### Step 2 — Prepare Docker Compose for ECR Images

The `web` service was updated to support both local builds and ECR images:

```yaml
web:
  image: "${WEB_IMAGE:-agrolink-web:local}"
  build:
    context: .
    dockerfile: docker/Dockerfile
```

If `WEB_IMAGE` is provided, Compose can use the specified ECR image. If it is not provided, the local image name `agrolink-web:local` is used.

This allows the existing local Docker workflow to remain unchanged while preparing the project for CI/CD deployment.

---

### Step 3 — Verify the Updated Local Docker Configuration

The updated Compose configuration was checked using:

```bash
docker compose config
```

The application was then rebuilt and started locally using:

```bash
docker compose up -d --build
```

The application continued to run successfully, confirming that the ECR-related Compose change did not break the existing Docker setup.

---

### Step 4 — Push Docker Image to Amazon ECR

Before automating the process, the Docker image push to Amazon ECR was tested manually.

Since the local Mac uses `arm64` while the EC2 instance uses `x86_64`, the image was built for the EC2-compatible `linux/amd64` platform.

The current Git commit SHA was used as the image tag:

```text
6d4d685
```
Docker was authenticated with ECR:

```bash
aws ecr get-login-password --region ap-south-1 | \
docker login --username AWS --password-stdin \
947200279241.dkr.ecr.ap-south-1.amazonaws.com
```

The image was then built and pushed:

```bash
docker buildx build \
  --platform linux/amd64 \
  -f docker/Dockerfile \
  -t 947200279241.dkr.ecr.ap-south-1.amazonaws.com/agrolink-web:6d4d685 \
  --push .
```
![authenication and tag built](screenshots/04-/login-to-aws-cli.png)

The SHA-tagged image was successfully verified in the private ECR repository.

![Tag is verified](<screenshots/04-/adding-SHA- tag.png>)

---

### Step 5 — Pull the ECR Image on EC2

![Login to AWS-CLI](screenshots/04-/login-to-aws-cli.png)

AWS CLI was installed on the EC2 instance and the instance was given read access to Amazon ECR using an IAM role with the `AmazonEC2ContainerRegistryReadOnly` policy.

![IAM Role added to EC2 in aws console](screenshots/04-/addingIAM-role.png)

Verified the EC2 role
![IAM Role verified in EC2](screenshots/04-/verifying-EC2-role.png)

Docker was authenticated to the private ECR registry, and the SHA-tagged AgroLink image was pulled successfully:

```bash
docker pull 947200279241.dkr.ecr.ap-south-1.amazonaws.com/agrolink-web:6d4d685

```

![Pulled the ECR image from EC2](screenshots/04-/pulled-ecr-image.png)

---

### Step 6 — Deploy the ECR Image with Docker Compose

The EC2 `compose.yaml` was updated from the GitHub repository to support the ECR image configuration.

The required SHA-tagged image was specified using:

```bash
export WEB_IMAGE=947200279241.dkr.ecr.ap-south-1.amazonaws.com/agrolink-web:6d4d685

```
The web service was then recreated using the ECR image without rebuilding it on EC2 and then the running container image was verified.

![Build the Web Service using ECR Image](<screenshots/04-/web-service-using-ecr-image.png>)

The application was successfully served using the SHA-tagged image pulled from Amazon ECR.

---

### Step 7 — Automate EC2 Deployment with `deploy.sh`

A reusable deployment script was added under:

```text
scripts/deploy.sh
```

![Adding the script to github](<screenshots/04-/adding-the-script.png>)

The script accepts an ECR image tag and performs the EC2 deployment automatically:

![Deployment completed successfully](screenshots/04-/deployment-using-script.png)


### Step 8 — Configure GitHub Actions Authentication

Now we get to the actual **GitHub Actions** part.

We need GitHub to be able to do two things:

```text
GitHub Actions
   │
   ├── AWS authentication
   │      ↓
   │    Build + push image → ECR
   │
   └── SSH authentication
          ↓
        EC2 → run deploy.sh <new-SHA>
```
First part is done. GitHub Actions was configured to authenticate with AWS using OpenID Connect (OIDC) and an IAM role.

![Made the new IAM role for Github Actions](documentation/screenshots/04-/new-IAM-role.png)

This allowed the workflow to access Amazon ECR without storing long-lived AWS access keys in the repository.

The workflow was given permission to:

```yaml
permissions:
  id-token: write
  contents: read
```
![Added GitHub actions workflow](screenshots/04-/githubactions-CI-flow.png)

GitHub Actions successfully authenticated with AWS and Amazon ECR using temporary credentials.

### Step 9 — Automate Docker Build and ECR Push

A GitHub Actions workflow was configured to run on pushes to the `main` branch.

The workflow:

```text
Checkout repository
        ↓
Authenticate to AWS
        ↓
Login to Amazon ECR
        ↓
Validate Docker Compose
        ↓
Build linux/amd64 Docker image
        ↓
Tag image with Git commit SHA
        ↓
Push image to Amazon ECR
```

![workflow success](screenshots/04-/workflow-success.png)

The workflow completed successfully and a new SHA-tagged AgroLink image was verified in the private ECR repository.

### Step 10 — Automate Deployment to EC2

After the Docker image was successfully built and pushed to ECR, a deployment job was added to GitHub Actions to connect to EC2 through SSH and execute `deploy.sh` using the current Git commit SHA.

The first automated deployment successfully:

- connected to EC2 through SSH,
- pulled the latest repository changes,
- authenticated with Amazon ECR,
- pulled the correct SHA-tagged Docker image,
- recreated the web container,
- and confirmed that MySQL was healthy.

However, the deployment verification failed with:

```text
curl: (56) Recv failure: Connection reset by peer
Application check failed.
```
![Error](screenshots/04-/failed-deployment.png)
![HTTP check failed](screenshots/04-/first-attempt-failed.png)

The web container had started, but the HTTP check was performed before the application was fully ready to accept requests.

Resolution: The deployment script was updated to retry the HTTP application check for a short period instead of failing immediately after container startup.

![successful full CI/CD Pipeline](screenshots/04-/final-workflow-success.png)
This improved the deployment verification by distinguishing between a started container and an application that is actually ready to serve traffic.
![application runs in the browser](screenshots/04-/browser-running.png)

---

## 5. Failure Handling and End-to-End Verification

The CI/CD pipeline was tested through actual deployment attempts rather than only validating the workflow configuration.

The first automated deployment demonstrated that a running container does not necessarily mean the application is ready to serve requests. Although the web container had started and MySQL was healthy, the immediate HTTP check failed because the application was still initializing.

The readiness retry added to `deploy.sh` resolved this issue by allowing the application time to become available before marking the deployment as failed.

The final end-to-end test completed successfully:

```text
git push
   ↓
GitHub Actions
   ↓
Validate configuration
   ↓
Build linux/amd64 image
   ↓
Push SHA-tagged image to ECR
   ↓
SSH to EC2
   ↓
Run deploy.sh with commit SHA
   ↓
Pull exact ECR image
   ↓
Update Docker Compose services
   ↓
MySQL health check
   ↓
Application readiness check
   ↓
Deployment successful
```

Both the `build-and-push` and `deploy` jobs completed successfully, and the deployed AgroLink application was verified through the EC2 public address.

---

## 6. Final Deployment State

At the end of this phase:

- AgroLink runs as Docker containers on AWS EC2.
- The web application image is built by GitHub Actions instead of being rebuilt on EC2.
- Versioned web images are stored in the private Amazon ECR repository.
- Each image is tagged using its Git commit SHA for traceability.
- GitHub Actions authenticates with AWS using OIDC and temporary credentials.
- EC2 accesses ECR through an IAM role with read-only ECR permissions.
- GitHub Actions connects to EC2 through SSH to initiate deployment.
- `deploy.sh` provides a consistent deployment procedure on EC2.
- Docker Compose manages the web and MySQL containers.
- MySQL data is stored using persistent Docker storage.
- MySQL health and application readiness are checked before deployment is considered successful.

A push to the `main` branch can now trigger the complete CI/CD process without manually building, transferring, or deploying the web application.

---

## 7. Skills and Lessons Learned

This phase provided practical experience with:

- GitHub Actions and CI/CD workflow design
- Docker image building and versioning
- Multi-platform Docker builds
- Amazon ECR
- AWS IAM roles and permissions
- OIDC authentication between GitHub Actions and AWS
- EC2 deployment automation
- SSH-based remote deployment
- Bash deployment scripting
- Docker Compose
- Container health checks
- Application readiness checks
- Environment variables and secret handling
- Troubleshooting automated deployments

A key lesson was the difference between **container state** and **application readiness**. A container can be running while the service inside it is still initializing, so deployment verification should check the actual application response.

Another important improvement was separating the **build** and **deployment** stages. The Docker image is built once by GitHub Actions, stored in ECR with a unique commit SHA, and the exact same image is then deployed to EC2. This avoids rebuilding the application on the deployment server and makes each deployment traceable to its source commit.

---

## 8. Phase Result

The AgroLink deployment process progressed from a manual EC2 deployment to a containerized and automated CI/CD workflow.

The final implementation follows:

```text
Source Code
    ↓
GitHub
    ↓
GitHub Actions
    ↓
Docker Image
    ↓
Amazon ECR
    ↓
EC2 Deployment
    ↓
Docker Compose
    ↓
AgroLink
```

The core CI/CD implementation is complete. Future improvements can include AWS Systems Manager (SSM) for deployment without direct SSH access, automated database migrations, HTTPS and domain configuration, and more comprehensive application testing.