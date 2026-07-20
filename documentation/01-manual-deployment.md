# AgroLink DevOps Documentation

# 01 – Manual Deployment of AgroLink on AWS EC2

> Portfolio documentation for the manual deployment phase before Dockerization.

## Table of Contents
1. Overview
2. Objectives
3. Technologies
4. Architecture
5. Deployment Workflow
6. Deployment Steps
7. Troubleshooting
8. Testing
9. Lessons Learned
10. Skills Gained
11. Next Phase

---

# 1. Overview

The objective of this deployment was to host the AgroLink PHP MVC application on an Ubuntu EC2 instance **without Docker**. This manual deployment was performed to understand Linux administration, networking, web servers, PHP runtime configuration, MySQL, and troubleshooting before introducing containerization.

# 2. Objectives

- Deploy AgroLink manually on AWS EC2
- Configure Nginx + PHP-FPM
- Configure MySQL
- Make the application publicly accessible
- Learn real deployment workflows

# 3. Technologies

| Component        | Technology                |
| ---------------- | ------------------------- |
| Cloud Provider   | AWS EC2                   |
| Operating System | Ubuntu Server             |
| Web Server       | Nginx                     |
| PHP Runtime      | PHP 8.3 + PHP-FPM         |
| Database         | MySQL 8                   |
| Version Control  | Git                       |
| Application      | AgroLink (Custom PHP MVC) |
| Remote Access    | SSH                       |

# 4. Architecture

```
                    Browser
                       │
                       ▼
                  Internet
                       │
                       ▼
                 AWS Elastic IP
                       │
                       ▼
                EC2 Security Group
                       │
                       ▼
                Ubuntu EC2 Server
                       │
          ┌────────────┴────────────┐
          ▼                         ▼
        Nginx                   PHP-FPM
          │                         │
          └────────────┬────────────┘
                       ▼
               AgroLink Application(PHP MVC)
                       │
                       ▼
                    MySQL Server
```

# 5. Deployment Workflow

```
        Launch EC2
            ↓
        SSH into Server
            ↓
        Install Nginx
            ↓
        Install PHP + PHP-FPM
            ↓
        Install MySQL
            ↓
        Clone AgroLink
            ↓
        Import Database
            ↓
        Configure config.php
            ↓
        Configure Nginx
            ↓
        Test
            ↓
        Deploy Publicly
```

# 6. Deployment Steps

## Step 1 – Launch EC2

- Ubuntu LTS
- Open ports 22, 80 and 443
- Allocate and associate an Elastic IP

**Why?**

Elastic IP ensures the server keeps the same public IP after stopping and starting the instance.
 ![EC2 instance](screenshots/EC2%20instance.png)

---

## Step 2 – Connect via SSH

The server was accessed securely using SSH.

```bash
ssh -i <key>.pem ubuntu@<ELASTIC_IP>
```

SSH provides secure remote administration.

---

## Step 3 – Install Required Packages

Installed:

- Git
- Nginx
- PHP 8.3
- PHP-FPM
- MySQL Server

Verified services using:

```bash
systemctl status nginx
systemctl status php8.3-fpm
systemctl status mysql
```
![systemctl status outputs](screenshots/status%20outputs.png)
![nginx status output](screenshots/nginx%20status.png)

---

## Step 4 – Clone AgroLink

Repository cloned into  ```/var/www/agrolink```

```bash
cd /var/www
sudo git clone <repository>
```

Project structure:

```
agrolink/
│
├── app/
├── database/
└── public/
```
---

## Step 5 – Configure MySQL

Create database and a dedicated user.

Import:

```bash
mysql -u username -p agrolink < agrolink.sql
```

Update database credentials in:

```text
app/core/config.php
```

---

## Step 6 – Configure PHP

Update from XAMPP configuration to Linux server credentials.

- DBHOST
- DBNAME
- DBUSER
- DBPASS

Verify PHP-FPM:

```bash
systemctl status php8.3-fpm
```

---

## Step 7 – Configure Nginx

Set document root:

```text
/var/www/agrolink/public
```

Configure PHP socket:

```text
/run/php/php8.3-fpm.sock
```

Use:

```nginx
try_files $uri $uri/ /index.php?$query_string;
```

### Why no .htaccess?

Apache reads `.htaccess` on every request.

Nginx does not read .htaccess files, resulting in improved performance because request routing is handled centrally.

---

## Step 8 – Test Configuration

```bash
sudo nginx -t
```

Expected:

```text
syntax is ok
test is successful
```
This prevents downtime caused by configuration errors.

![test configuration](screenshots/nginx%20testing.png)

Reload:

```bash
sudo systemctl reload nginx
```

---

## Step 9 – Test Deployment

Visit:

```text
http://<EC2_ELASTIC_IP>
```

Verified:

- Application loads
- MVC routing works
- Database connection works

![deployed website](screenshots/deployed%20agrolink%20homepage.png)

---

# 7. Troubleshooting

## Issue 1 – Public IP Changed After Restart

**Cause**

Stopping an EC2 instance without an Elastic IP changes its public IP.

**Solution**

Allocate and associate an Elastic IP. 

**Lesson Learned**

Production deployments require stable public addresses.

---

## Issue 2 – Database Import Error

The SQL dump contained compatibility issues originating from the local development database.

**Solution**

Clean the SQL dump and re-import.

**Lesson Learned**

Database portability should be considered when migrating between database engines.

---

## Issue 4 – Nginx Failed to Start

**Error**

```
bind() to 0.0.0.0:80 failed
```

**Cause**

Port 80 was already occupied.

**Solution**

Identified the conflicting service and Stop the conflicting service and restart Nginx.

---

## Issue 5 – Upload Directory Not Writable

Symptoms:

Application reported the verification upload directory was not writable.

Investigation:

- Checked ownership
- Checked permissions
- Verified PHP-FPM user
- Confirmed upload path

Current status:

Application-level debugging required.

Lesson Learned

Not every deployment issue is caused by infrastructure, application logic can also affect runtime behavior.

---

# 8. Testing

Verified:

- SSH connectivity
- Nginx running
- PHP-FPM running
- MySQL running
- Database connectivity
- Public accessibility
- MVC routing

Commands used:

```bash
systemctl status nginx
systemctl status php8.3-fpm
systemctl status mysql

nginx -t
```

---

# 9. Lessons Learned

- Manual deployment provides a deep understanding of web infrastructure.
- Nginx replaces Apache's `.htaccess` using `try_files`.
- PHP-FPM executes PHP while Nginx serves static files.
- Correct Linux file permissions are essential.
- Elastic IPs prevent address changes.
- Testing configuration before restarting services prevents downtime.

---

# 10. Skills Gained

- Linux administration
- SSH
- Git on remote servers
- Nginx configuration
- PHP-FPM
- MySQL administration
- File permissions
- AWS EC2
- Elastic IP
- Security Groups
- Service management
- Reading system logs
- Troubleshooting deployment issues

---

# 11. Screenshots to Include

-
- SSH terminal

- nginx -t output
- Elastic IP configuration

---

# 12. Next Phase

The next phase is **Dockerization**.

The objective is to eliminate environment-specific configuration, simplify deployment, and prepare the application for CI/CD pipelines and orchestration platforms such as Kubernetes.

Goals:

- Containerize AgroLink
- Docker Compose
- Nginx container
- PHP container
- MySQL container
- phpMyAdmin
- Persistent volumes
- Container networking

This manual deployment will serve as the baseline for comparing traditional deployments with containerized deployments.
