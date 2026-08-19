# AgroLink — DevOps Deployment & CI/CD

## Overview

AgroLink is a web-based agricultural marketplace developed as a university project. This repository contains the **DevOps extension** of the application, focusing on making its deployment reproducible, containerized, and automated.

The project evolved through four stages:

```text
Manual EC2 Deployment
        ↓
Docker Containerization
        ↓
Containerized EC2 Deployment
        ↓
Automated CI/CD
```

The final implementation uses **GitHub Actions, Docker, Amazon ECR, AWS EC2, Docker Compose, Bash, and AWS IAM/OIDC** to build, version, publish, and deploy the application automatically.

---

## Project Goals

The main goal was to transform the original manually deployed application into a more reliable and repeatable deployment workflow.

The DevOps implementation focuses on:

- Containerizing the application and database.
- Reproducing the application environment using Docker.
- Deploying the containerized application to AWS EC2.
- Managing persistent MySQL data using Docker volumes.
- Implementing database health checks.
- Automating Docker image builds.
- Publishing versioned images to Amazon ECR.
- Deploying exact image versions to EC2.
- Automating remote deployment using Bash.
- Using GitHub Actions for CI/CD.
- Using AWS IAM and OIDC for secure CI/CD authentication.
- Adding application readiness checks to prevent false-positive deployments.

---

# Final Architecture

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
                    Image  │     │ SSH
                           │     │
                           ▼     ▼
                    ┌─────────┐ ┌───────────────┐
                    │ Amazon  │ │     AWS EC2   │
                    │   ECR   │ │               │
                    │         │ │   deploy.sh   │
                    │ SHA-tag │ │       ↓       │
                    │  image  │ │ Docker Compose│
                    └────┬────┘ │   ┌────┴────┐ │
                         │      │   ▼         ▼ │
                         └─────►│  Web       MySQL
                                │ Container  Container
                                │               │
                                │          Health Check
                                │               │
                                │      Application Readiness
                                └───────────────┘
```

### Deployment Flow

```text
git push
   │
   ▼
GitHub Actions
   │
   ├── Validate configuration
   │
   ├── Build Docker image
   │
   ├── Build for linux/amd64
   │
   └── Push image to Amazon ECR
               │
               ▼
        SHA-tagged image
               │
               ▼
        SSH to EC2
               │
               ▼
          deploy.sh
               │
               ▼
      Pull exact image
               │
               ▼
        Docker Compose
               │
          ┌────┴────┐
          ▼         ▼
         Web       MySQL
          │         │
          │     Health Check
          │         │
          └────┬────┘
               ▼
      Application Readiness
               │
               ▼
        Deployment Success
```

---

# Technology Stack

| Technology | Purpose |
|---|---|
| PHP | Application backend/runtime |
| Apache | Web server inside the application container |
| MySQL 8.0.45 | Database |
| Docker | Application containerization |
| Docker Compose | Multi-container orchestration |
| Bash | Deployment automation |
| Git | Version control |
| GitHub | Source code repository |
| GitHub Actions | CI/CD automation |
| Amazon EC2 | Application deployment server |
| Amazon ECR | Private Docker image registry |
| AWS IAM | AWS permissions and access control |
| AWS OIDC | Secure GitHub Actions authentication |
| Amazon EBS | EC2 storage |

---
---

# Repository Structure

```text
AgroLink-DevOps/
│
├── .github/
│   └── workflows/
│       └── ...
│
├── docker/
│   └── Dockerfile
│
├── documentation/
│   ├── 01-manual-deployment.md
│   ├── 02-containerization.md
│   ├── 03-containerized-deployment.md
│   └── 04-ci-cd-automation.md
│
├── screenshots/
│   ├── 02-containerization/
│   ├── 03-containerized-deploy/
│   └── ...
│
├── scripts/
│   └── deploy.sh
│
├── compose.yaml
├── .dockerignore
├── .gitignore
└── README.md
```
---
# Project Phases

## Phase 1 — Manual EC2 Deployment

AgroLink was first deployed manually to an AWS EC2 Ubuntu instance by configuring Nginx, PHP, MySQL, application dependencies, networking, and Security Group rules.

This established the baseline deployment and provided hands-on experience with Linux server configuration and cloud deployment.

---

## Phase 2 — Docker Containerization

The application was containerized using Docker and Docker Compose, separating the web application and MySQL database into individual services.

This phase introduced reproducible environments, Docker networking, persistent MySQL storage, database initialization, and health checks.

---

## Phase 3 — Containerized EC2 Deployment

The Dockerized application was deployed to EC2 using Docker Compose.

A staged migration was used by initially running the containerized application on port `8080` before moving it to port `80` and disabling the previous native Nginx, PHP-FPM, and MySQL services.

An EC2 disk-space issue encountered during deployment was resolved by expanding the EBS volume from 8 GiB to 20 GiB.

---

## Phase 4 — CI/CD Automation

The deployment process was automated using GitHub Actions and Amazon ECR.

GitHub Actions now validates the configuration, builds a `linux/amd64` Docker image, tags it using the Git commit SHA, and publishes it to the private ECR repository.

After a successful build, GitHub Actions connects to EC2 through SSH and executes `deploy.sh`, which pulls and deploys the exact SHA-tagged image using Docker Compose.

AWS authentication between GitHub Actions and AWS uses OIDC and IAM instead of long-lived AWS access keys.

---

# CI/CD Workflow

The final workflow is:

```text
Developer
   │
   │ git push
   ▼
GitHub
   │
   ▼
GitHub Actions
   │
   ├── Validate
   │
   ├── Build
   │
   ├── Tag with commit SHA
   │
   └── Push to ECR
             │
             ▼
        Amazon ECR
             │
             │ SSH
             ▼
           EC2
             │
             ▼
         deploy.sh
             │
             ▼
       Pull SHA image
             │
             ▼
       Docker Compose
             │
       ┌─────┴─────┐
       ▼           ▼
     Web          MySQL
       │           │
       │      Health Check
       │           │
       └─────┬─────┘
             ▼
    Application Readiness
             │
             ▼
      Deployment Complete
```

A push to the `main` branch can trigger the complete build-and-deploy process.

---

# Documentation

Detailed implementation steps, screenshots, troubleshooting, and technical decisions are available in the `documentation/` directory.

| Phase.  | Documentation |
|---------|---|
| Phase 1 | [Manual EC2 Deployment](documentation/01-manual-deployment.md) |
| Phase 2 | [Docker Containerization](documentation/02-containerization.md) |
| Phase 3 | [Containerized EC2 Deployment](documentation/03-containerized-deployment.md) |
| Phase 4 | [CI/CD Automation](documentation/04-ci-cd-automation.md) |

Each phase documents the progression from the previous implementation, including configuration changes, verification steps, encountered issues, and their resolutions.

---

# Key Technical Highlights

- **Build once, deploy the same artifact** — Docker images are built in GitHub Actions, stored in Amazon ECR, and pulled by EC2 without rebuilding on the deployment server.

- **SHA-based image versioning** — Each Docker image is tagged using its Git commit SHA, providing traceability between source code, container image, and deployment.

- **Secure AWS authentication** — GitHub Actions uses OIDC with AWS IAM to obtain temporary credentials instead of storing long-lived AWS access keys.

- **IAM-based ECR access** — EC2 uses an IAM role with read access to the private ECR repository.

- **Persistent database storage** — MySQL data is stored in a Docker named volume so container recreation does not remove existing database data.

- **Database health checking** — Docker Compose waits for MySQL to become healthy before starting the dependent web service.

- **Application readiness verification** — `deploy.sh` retries HTTP checks after deployment instead of assuming that a started container means the application is ready.

- **Architecture compatibility** — CI builds the application image for `linux/amd64`, matching the x86_64 EC2 deployment environment.

- **Automated remote deployment** — A successful CI build is followed by an SSH-based EC2 deployment using a reusable Bash deployment script.

- **Failure-aware pipeline** — Validation, build, database health, and application readiness failures stop the deployment instead of reporting a false success.

---

# Key Learning Outcomes

The project demonstrated several important DevOps concepts in practice.

### Reproducibility

Docker replaced manually configured application environments with reproducible container images.

### Automation

Manual build and deployment steps were replaced with an automated CI/CD pipeline.

### Traceability

Git commit SHAs are used as Docker image tags, allowing deployments to be traced back to source code.

### Reliability

Health checks and application readiness checks prevent deployment success from being determined only by container startup.
----

# Future Improvements

The core DevOps implementation is complete. Possible future extensions include:

- Replace direct SSH deployment with AWS Systems Manager (SSM).
- Configure HTTPS and a custom domain.
- Introduce automated database migrations.
- Add container image vulnerability scanning.
- Expand automated application testing before deployment.
- Add monitoring and centralized logging.
- Implement automated rollback for failed deployments.
- Manage AWS infrastructure using Terraform.
- Explore Kubernetes orchestration as the application architecture grows.

---