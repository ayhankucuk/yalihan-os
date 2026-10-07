# YALIHAN OS — Infrastructure & Server Registry

> **Classification:** Internal Operational Infrastructure Inventory  
> **Authority:** Governance SSOT (`.project-brain`)  
> **Security Policy:** Secrets-free document. **NEVER** store private keys, passwords, API tokens, or credentials in this file. Only client-side filesystem paths may be documented.

---

## 🛰️ 1. SERVER: HERMES

- **Name:** Hermes
- **Role:** Production n8n & Automation Host
- **Provider:** Oracle Cloud Infrastructure (OCI)
- **Tenancy / Account:** `ayhankucuk`
- **Region:** Australia East (Sydney) / `ap-sydney-1`
- **Public IPv4:** `159.13.59.128`
- **Private IPv4:** `10.0.0.68`
- **Shape:** `VM.Standard.E2.1.Micro`
- **OS:** Ubuntu 22.04 LTS
- **Architecture:** x86_64 (1 GB class memory, OCI block storage)
- **SSH User:** `ubuntu`
- **SSH Private Key Path (Local Mac Only):** `~/.ssh/oracle/hermes.key` *(PATH ONLY)*
- **Workload / Runtime Services:**
  - Production Self-Hosted n8n (`https://n8n.yalihanemlak.com.tr`, Version: `2.39.8`)
  - Docker Services (`hermes-n8n`, `cloudflared`, Nginx, MySQL, Redis)
- **Evidence Level:** `PRODUCTION_VERIFIED`
- **Evidence Type:** `TOOL_RUNTIME`
- **Last Verified Date:** `2026-09-18`
- **Scope Limitation Note:** Proves observed host and runtime state only. Does **NOT** imply all n8n workflows or business flows are production verified.

---

## 🌐 2. SERVER: ATLAS

- **Name:** Atlas
- **Role:** YALIHAN OS Infrastructure Node
- **Current Workload:** `NONE / UNASSIGNED` *(Not classified as n8n backup)*
- **Provider:** Oracle Cloud Infrastructure (OCI)
- **Tenancy / Account:** `info1993`
- **Region:** Germany Central (Frankfurt) / `eu-frankfurt-1`
- **Instance Display Name:** Atlas
- **Observed Hostname:** `instance-20260918-2253`
- **Public IPv4:** `138.3.252.77`
- **Private IPv4:** `10.0.0.43`
- **VCN:** `yalihanemlak`
- **Subnet:** `public subnet-yalihanemlak`
- **OS:** Ubuntu 24.04.4 LTS (Kernel: `6.17.0-1020-oracle`)
- **Architecture:** x86_64
- **CPU (Observed):** AMD EPYC 7551 (Linux visible CPUs: 2)
- **RAM (Observed):** ~954 MiB
- **Root Disk (Observed):** ~45 GB
- **Shape:** `UNKNOWN` *(Inferred from CPU/RAM — OCI console/metadata shape unverified)*
- **Listening Services (Clean Instance):** SSH (:22), rpcbind (:111), systemd-resolved
- **Swap:** None
- **SSH User:** `ubuntu`
- **SSH Alias (Local Mac):** `atlas` (`ssh atlas`)
- **SSH Private Key Path (Local Mac Only):** `~/.ssh/oracle/atlas.key` *(PATH ONLY)*
- **SSH Public Key Path (Local Mac Only):** `~/.ssh/oracle/atlas.key.pub`
- **Connection Status:** Direct SSH connection from Mac tested successfully.
- **Evidence Level:** `PRODUCTION_VERIFIED`
- **Evidence Type:** `TOOL_RUNTIME`
- **Last Verified Date:** `2026-09-18`

---

## ❓ 3. SERVER 3 (HISTORICAL TARGET)

- **Historical Identifier / Target:** `158.180.7.61`
- **Historical SSH Command Referenced:** `ubuntu@158.180.7.61`
- **Current Connectivity Test:** TCP/22 timed out from Mac (Authentication never reached).
- **Current Identity:** `UNKNOWN`
- **Current Provider / Tenancy:** `UNKNOWN`
- **Current Role:** `UNKNOWN`
- **Current Runtime State:** `UNKNOWN` *(Not classified as dead/deleted based solely on TCP timeout)*
- **Evidence Level:** `UNKNOWN`
- **Evidence Type:** `HISTORICAL_REFERENCE`
- **Notes:** Recorded strictly as historical target reference pending further verification.

---

## 🔒 SECURITY & SECRETS AUDIT

- **Private Keys Included:** `NONE` (Only local client filesystem paths documented).
- **Passwords / Tokens / API Keys Included:** `NONE`.
- **Secrets Scanning Result:** `PASS` — Zero secret material introduced.

---

## 💡 FUTURE CANDIDATE IDEA (UNIMPLEMENTED)

> **Title:** Hermes DR / Monitoring Node  
> **Problem:** Hermes currently hosts production n8n and same-host backup is not independent disaster recovery.  
> **Proposed Improvement:** Evaluate Atlas or another free OCI node for encrypted off-host backups, monitoring, and recovery support.  
> **Expected Benefit:** Independent recovery path and infrastructure monitoring.  
> **Affected Domain:** Infrastructure / n8n / DR  
> **Risk:** Medium  
> **Evidence:** Atlas exists and is currently unassigned.  
> **Suggested Priority:** Candidate  
> **Requires Separate Router Task:** `true`  
> *Note: This candidate idea does NOT alter Atlas configuration or workload in this task.*
