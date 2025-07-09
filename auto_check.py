import winreg
import psutil
import getpass
import mysql.connector
import re
import requests
import subprocess
import os
from datetime import datetime


def get_clean_username():
    raw_username = getpass.getuser()
    return re.sub(r'\W+', '_', raw_username).lower()

def get_db_connection():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='auto_check'
    )

def initialize_db():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS checked_result (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            app_or_service_name VARCHAR(255),
            availability VARCHAR(50),
            status_detail VARCHAR(50),
            checked_at DATETIME,
            UNIQUE KEY unique_entry (username, app_or_service_name)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ''')
    cursor.execute("TRUNCATE TABLE checked_result")
    conn.commit()
    return conn

def get_apps_to_check():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT app_name FROM app_table")
    apps = [row[0] for row in cursor.fetchall()]
    conn.close()
    return apps

def get_services_to_check():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT service_name FROM service_table")
    services = [row[0] for row in cursor.fetchall()]
    conn.close()
    return services

def get_websites_to_check():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT website_url FROM website_table")
    websites = [row[0] for row in cursor.fetchall()]
    conn.close()
    return websites

def get_installed_apps():
    installed_apps = set()
    registry_paths = [
        r"SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall",
        r"SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall"
    ]
    for path in registry_paths:
        for root in (winreg.HKEY_LOCAL_MACHINE, winreg.HKEY_CURRENT_USER):
            try:
                with winreg.OpenKey(root, path) as key:
                    for i in range(winreg.QueryInfoKey(key)[0]):
                        try:
                            subkey_name = winreg.EnumKey(key, i)
                            with winreg.OpenKey(key, subkey_name) as subkey:
                                try:
                                    name, _ = winreg.QueryValueEx(subkey, "DisplayName")
                                    try:
                                        sys_component, _ = winreg.QueryValueEx(subkey, "SystemComponent")
                                        if sys_component == 1:
                                            continue
                                    except FileNotFoundError:
                                        pass
                                    installed_apps.add(name)
                                except FileNotFoundError:
                                    continue
                        except OSError:
                            continue
            except FileNotFoundError:
                continue
    return installed_apps

def check_services(service_list):
    status_dict = {}
    try:
        for service in psutil.win_service_iter():
            try:
                info = service.as_dict()
                name = info['name']
                if name in service_list:
                    status_dict[name] = info['status']
            except Exception:
                continue
    except Exception as e:
        print("Error accessing services:", e)
    return status_dict
def check_bluetooth_status():
    try:
        result = subprocess.run(
            ["powershell", "-Command", "(Get-Service bthserv).Status"],
            capture_output=True,
            text=True
        )
        output = result.stdout.strip()
        if "Running" in output:
            return "Available", "Bluetooth Service Running"
        elif "Stopped" in output:
            return "Available", "Bluetooth Service Stopped"
        else:
            return "Not Available", "Bluetooth Service Not Found"
    except Exception:
        return "Not Available", "Error"

import requests
from urllib.parse import urlparse

def check_website_accessibility(url):
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                      "AppleWebKit/537.36 (KHTML, like Gecko) "
                      "Chrome/114.0.0.0 Safari/537.36"
    }

    # Validate and normalize URL
    parsed = urlparse(url)
    if not parsed.scheme:
        url = "http://" + url  # default to HTTP if scheme is missing

    try:
        response = requests.get(url, headers=headers, timeout=5, allow_redirects=True, verify=True)
        code = response.status_code
        if 200 <= code < 300:
            return "Accessible"
        elif 300 <= code < 400:
            return f"Redirected (Status {code})"
        elif 400 <= code < 500:
            return f"Client Error (Status {code})"
        elif 500 <= code < 600:
            return f"Server Error (Status {code})"
        else:
            return f"Unknown Status ({code})"

    except requests.exceptions.SSLError:
        return "SSL Error - Certificate may be invalid"

    except requests.exceptions.Timeout:
        return "Timed out - Site took too long to respond"

    except requests.exceptions.ConnectionError as e:
        return f"Connection Error - {e}"

    except requests.exceptions.RequestException as e:
        return f"Request Failed - {str(e)}"


def check_bitlocker_status():
    try:
        result = subprocess.run(["manage-bde", "-status", "C:"], capture_output=True, text=True)
        if "Percentage Encrypted" in result.stdout:
            return "Available", "Encrypted"
        else:
            return "Available", "Not Encrypted"
    except Exception:
        return "Not Available", "Error"

def check_usb_connected():
    import winreg
    try:
        key_path = r"SOFTWARE\Policies\Microsoft\Windows\RemovableStorageDevices"
        key = winreg.OpenKey(winreg.HKEY_LOCAL_MACHINE, key_path)
        subkeys = []
        i = 0
        while True:
            try:
                subkey_name = winreg.EnumKey(key, i)
                subkeys.append(subkey_name)
                i += 1
            except OSError:
                break
        winreg.CloseKey(key)

        if subkeys:
            return "Restricted", f"Access blocked for: {', '.join(subkeys)}"
        else:
            return "Not Restricted", "No restrictions found in RemovableStorageDevices"
    except FileNotFoundError:
        return "Not Restricted", "No RemovableStorageDevices policy key found"
    except Exception as e:
        return "Error", str(e)

def check_usb_tethering():
    try:
        result = subprocess.run(["ipconfig"], capture_output=True, text=True)
        return ("Available", "Tethered") if "Remote NDIS" in result.stdout else ("Not Available", "No Tethering")
    except Exception:
        return "Not Available", "Error"

def check_vpn_connected():
    try:
        result = subprocess.run(["rasdial"], capture_output=True, text=True)
        return ("Available", "VPN Connected") if "No connections" not in result.stdout else ("Not Available", "No VPN")
    except Exception:
        return "Not Available", "Error"

def check_defender_status():
    try:
        cmd = ["powershell", "-Command", "Get-MpComputerStatus | Select-Object -ExpandProperty AMServiceEnabled"]
        result = subprocess.run(cmd, capture_output=True, text=True)
        return ("Available", "Running") if "True" in result.stdout else ("Available", "Stopped")
    except Exception:
        return "Not Available", "Error"

def count_chrome_extensions():
    try:
        path = os.path.expandvars(r"%LOCALAPPDATA%\Google\Chrome\User Data\Default\Extensions")
        if os.path.exists(path):
            count = len(os.listdir(path))
            return "Available", f"{count} Extensions"
        else:
            return "Not Available", "Not Found"
    except Exception:
        return "Not Available", "Error"

def check_service_running(service_name):
    try:
        result = subprocess.run(["sc", "query", service_name], capture_output=True, text=True)
        return "RUNNING" in result.stdout
    except Exception:
        return False

def check_extended_services(username, cursor):
    extended_checks = {
        "BitLocker": check_bitlocker_status,
        "Bluetooth": check_bluetooth_status,
        "USB Device": check_usb_connected,
        "USB Tethering": check_usb_tethering,
        "VPN Services": check_vpn_connected,
        "Trellix": lambda: ("Available", "Running" if check_service_running("macmnsvc") else "Stopped"),
        "CrowdStrike": lambda: ("Available", "Running" if check_service_running("CSFalconService") else "Stopped"),
        "Microsoft Defender": check_defender_status,
        "Chrome Extensions": count_chrome_extensions,
    }

    for service, check_fn in extended_checks.items():
        availability, status = check_fn()
        cursor.execute('''
            INSERT INTO checked_result (username, app_or_service_name, availability, status_detail, checked_at)
            VALUES (%s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE availability = VALUES(availability), status_detail = VALUES(status_detail), checked_at = VALUES(checked_at)
        ''', (username, service, availability, status, datetime.now()))

def main():
    print("please Wait till the check is over...\n")
    username = get_clean_username()
    conn = initialize_db()
    cursor = conn.cursor()

    apps_to_check = get_apps_to_check()
    services_to_check = get_services_to_check()
    websites_to_check = get_websites_to_check()
    installed_apps = get_installed_apps()

    for app in apps_to_check:
        is_installed = any(any(keyword.lower() in installed.lower() for keyword in app.split()) for installed in installed_apps)
        availability = "Available" if is_installed else "Not Available"
        cursor.execute('''
            INSERT INTO checked_result (username, app_or_service_name, availability, status_detail, checked_at)
            VALUES (%s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE availability = VALUES(availability), status_detail = VALUES(status_detail), checked_at = VALUES(checked_at)
        ''', (username, app, availability, None, datetime.now()))

    service_results = check_services(services_to_check)
    for svc in services_to_check:
        if svc in service_results:
            availability = "Available"
            status_detail = service_results[svc].capitalize()
        else:
            availability = "Not Available"
            status_detail = "Not Found"
        cursor.execute('''
            INSERT INTO checked_result (username, app_or_service_name, availability, status_detail, checked_at)
            VALUES (%s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE availability = VALUES(availability), status_detail = VALUES(status_detail), checked_at = VALUES(checked_at)
        ''', (username, svc, availability, status_detail, datetime.now()))

    check_extended_services(username, cursor)

    for url in websites_to_check:
        status = check_website_accessibility(url)
        availability = "Available" if status == "Accessible" else "Not Available"
        cursor.execute('''
            INSERT INTO checked_result (username, app_or_service_name, availability, status_detail, checked_at)
            VALUES (%s, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE availability = VALUES(availability), status_detail = VALUES(status_detail), checked_at = VALUES(checked_at)
        ''', (username, url, availability, status, datetime.now()))

    conn.commit()
    conn.close()
    print(f"Results saved")

if __name__ == "__main__":
    main()


