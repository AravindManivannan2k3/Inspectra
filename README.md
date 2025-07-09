# Inspectra

**Inspectra** is a powerful system inspection and monitoring tool built using **Python**, **PHP**, and **MySQL**. It automates the process of checking application availability, service statuses, website accessibility, and various system-level diagnostics on Windows systems.

---

## Features

-  Detects installed applications using Windows Registry
-  Checks status of Windows services via `psutil`
-  Verifies website accessibility with HTTP status codes and SSL handling
-  Inspects core system services like:
  - BitLocker
  - Microsoft Defender
  - VPN connectivity
  - USB tethering/restrictions
  - Bluetooth service
  - Trellix, CrowdStrike, etc.
-  Logs results into a structured **MySQL** database
-  Includes a **PHP dashboard** for real-time inspection result viewing
-  Prevents duplicate entries using `ON DUPLICATE KEY UPDATE`

---

##  Tech Stack

| Component | Technology |
|----------|------------|
| Backend  | Python 3, psutil, subprocess, Windows Registry |
| Database | MySQL |
| Frontend | PHP (dashboard interface) |
| OS       | Windows only (due to system-level checks) |

---
