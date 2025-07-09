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
> **Deployment Note:**  
> Inspectra is intended to run on **multiple machines**. You can:
> - Convert the python scrypt to an app and then schedule the application to run at boot/login using Task Scheduler.
> - Trigger it manually or remotely via admin tools or scripts.
> - Each run pushes results to the **same shared MySQL database** (specified in the script), enabling unified reporting and analysis.

# Database Setup Instructions

1. **Create a MySQL database** named `auto_check` using any MySQL client (like phpMyAdmin, MySQL Workbench, or command line).

2. **Inside the `auto_check` database**, create the following tables:

   - `app_table`: for storing the names of applications you want to check.
   - `website_table`: for storing URLs of websites to test for accessibility.

3. There’s no need to manually create the `checked_result` table.  
    The Python script (`auto_check.py`) will automatically create and manage it when you run it for the first time.

4. Populate `app_table`, and `website_table` with the values you want to inspect. These can be added manually or via the included `manage.php` script.

![image](https://github.com/user-attachments/assets/809279f0-e9dd-4b89-b729-c694ef84d1ca)

![image](https://github.com/user-attachments/assets/6623537e-68cc-4e00-9624-1d140729a229)

