from flask import Flask, jsonify, render_template, send_file, redirect, session
from flask_cors import CORS
import subprocess
import speedtest
import logging
import datetime
from fpdf import FPDF
import re
import os

app = Flask(__name__)
CORS(app)

# Setup logging
logging.basicConfig(level=logging.INFO, format="%(asctime)s - %(levelname)s - %(message)s")

@app.route('/')
def home():
    return render_template('index.html')

@app.route('/about')
def about():
    return render_template('about.html')

@app.route('/speedtest')
def wifi_speedtest():
    return render_template('speedtest.html')

@app.route('/guide')
def guide():
    return render_template('guide.html')

@app.route('/faq')
def faq():
    return render_template('faq.html')

@app.route('/logout')
def logout():
    return redirect('http://localhost/Wi-Fi_Security_Auditing_Tool/database/login.php')  

# Function to run shell commands safely
def run_command(command):
    try:
        result = subprocess.run(command, capture_output=True, text=True, check=True, encoding="utf-8", errors="ignore")
        return result.stdout.strip()
    except subprocess.CalledProcessError as e:
        logging.error(f"Command failed: {e.stderr.strip()}")
        return None
    except Exception as e:
        logging.error(f"Unexpected error: {str(e)}")
        return None

def check_password_strength(password):
    if len(password) < 8 or password.isdigit():
        return "Weak"
    elif len(password) >= 8 and (re.search(r"[A-Z]", password) and re.search(r"[a-z]", password) and re.search(r"\d", password)):
        if len(password) >= 12 and re.search(r"[!@#$%^&*()_+]", password):
            return "Strong"
        return "Moderate"
    return "Weak"

@app.route('/scan_networks')
def scan_networks():
    output = run_command(['netsh', 'wlan', 'show', 'network'])
    if output is None:
        return jsonify({'error': 'Failed to scan networks'}), 500
    return jsonify({'output': output})

@app.route('/check_connected')
def check_connected():
    output = run_command(['netsh', 'wlan', 'show', 'interfaces'])
    if output is None:
        return jsonify({'error': 'Failed to retrieve connected network'}), 500
    return jsonify({'output': output})

@app.route('/audit_security')
def audit_security():
    output = run_command(['netsh', 'wlan', 'show', 'profiles'])
    if output is None:
        return jsonify({'error': 'Failed to retrieve security audit'}), 500
    return jsonify({'output': output})

@app.route('/check_vulnerabilities')
def check_vulnerabilities():
    connected_network = get_connected_network()

    vulnerabilities = {
        "open_network": False,
        "wep": False,
        "weak_password": False,
        "tkip_encryption": False,
        "default_ssid": False,
        "wps_enabled": False,
        "too_many_devices": False
    }

    if connected_network.get("encryption") == "Open":
        vulnerabilities["open_network"] = True
    
    if connected_network.get("encryption") == "WEP":
        vulnerabilities["wep"] = True
    
    if connected_network.get("encryption") == "TKIP":
        vulnerabilities["tkip_encryption"] = True
    
    if connected_network.get("ssid") in ["TP-LINK_1234", "Unifi123", "D-Link-5678"]:
        vulnerabilities["default_ssid"] = True

    device_count = get_connected_device_count()
    if device_count > 10:
        vulnerabilities["too_many_devices"] = True

    if vulnerabilities["open_network"] or vulnerabilities["wep"] or vulnerabilities["tkip_encryption"]:
        risk_level = "High"
    elif vulnerabilities["default_ssid"] or vulnerabilities["too_many_devices"]:
        risk_level = "Medium"
    else:
        risk_level = "Low"

    return jsonify({"connected_network": connected_network, "vulnerabilities": vulnerabilities, "risk_level": risk_level})

@app.route('/check_password_security')
def check_password_security():
    output = run_command(['netsh', 'wlan', 'show', 'profiles'])
    if output is None:
        return jsonify({'error': 'Failed to retrieve Wi-Fi profiles'}), 500

    profile_names = re.findall(r"All User Profile\s*:\s(.*)", output)
    passwords = {}

    for profile in profile_names:
        profile = profile.strip()
        profile_info = run_command(['netsh', 'wlan', 'show', 'profile', profile, 'key=clear'])
        if profile_info is None:
            continue

        password_match = re.search(r"Key Content\s*:\s(.*)", profile_info)
        if password_match:
            password = password_match.group(1)
            strength = check_password_strength(password)
            passwords[profile] = {"password": password, "strength": strength}
        else:
            passwords[profile] = {"password": "No password stored or hidden", "strength": "N/A"}

    return jsonify({'output': passwords})


# Optimized Speedtest Function
def run_speed_test():
    try:
        st = speedtest.Speedtest()
        st.get_best_server()

        download_speed = round(st.download() / 1_000_000, 2)
        upload_speed = round(st.upload() / 1_000_000, 2)
        ping = round(st.results.ping, 2)

        return {
            'download': max(download_speed, 0.1), 
            'upload': max(upload_speed, 0.1),
            'ping': max(ping, 1)  
        }
    except (speedtest.ConfigRetrievalError, speedtest.NoMatchedServers, speedtest.SpeedtestBestServerFailure):
        logging.error("Speedtest server issues.")
        return {'download': 0, 'upload': 0, 'ping': 9999, 'error': 'Speedtest server error.'}
    except speedtest.SpeedtestException as e:
        logging.error(f"Speedtest error: {str(e)}")
        return {'download': 0, 'upload': 0, 'ping': 9999, 'error': 'Speedtest failed.'}
    except Exception as e:
        logging.error(f"Unexpected error: {str(e)}")
        return {'download': 0, 'upload': 0, 'ping': 9999, 'error': 'Unexpected failure.'}

@app.route('/run_speedtest')
def run_speedtest():
    results = run_speed_test()
    if "error" in results:
        return jsonify(results), 500
    return jsonify(results)

# Helper Functions
def get_connected_network():
    try:
        output = run_command(['netsh', 'wlan', 'show', 'interfaces'])
        if not output:
            return {"ssid": "Unknown", "encryption": "Unknown"}

        ssid_match = re.search(r"SSID\s+:\s(.+)", output)
        encryption_match = re.search(r"Authentication\s+:\s(.+)", output)

        ssid = ssid_match.group(1).strip() if ssid_match else "Unknown"
        encryption = encryption_match.group(1).strip() if encryption_match else "Unknown"

        return {"ssid": ssid, "encryption": encryption}
    except Exception as e:
        logging.error(f"Error fetching network details: {e}")
        return {"ssid": "Unknown", "encryption": "Unknown"}

def get_connected_device_count():
    try:
        output = run_command("arp -a")
        if not output:
            return 0

        devices = re.findall(r"\d+\.\d+\.\d+\.\d+", output)
        return len(set(devices))
    except Exception as e:
        logging.error(f"Error fetching device count: {e}")
        return 0

@app.route('/generate_audit_report')
def generate_audit_report():
    # Fetch actual scan data
    networks = scan_networks().json.get("output", "No data available")
    connected_network = get_connected_network()
    vulnerabilities = check_vulnerabilities().json
    speed_test_results = run_speed_test()

    # Initialize PDF
    pdf = FPDF()
    pdf.set_auto_page_break(auto=True, margin=15)
    pdf.add_page()
    pdf.set_font("Arial", "B", 16)
    pdf.cell(200, 10, "Wi-Fi Security Audit Report", ln=True, align='C')
    pdf.ln(10)

    # Add Date
    pdf.set_font("Arial", "", 12)
    pdf.cell(200, 10, f"Generated on: {datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')}", ln=True, align='L')
    pdf.ln(10)

    # Connected Network Details
    pdf.set_font("Arial", "B", 14)
    pdf.cell(200, 10, "Connected Network Details", ln=True)
    pdf.set_font("Arial", "", 12)
    for key, value in connected_network.items():
        pdf.cell(200, 8, f"{key}: {value}", ln=True)

    pdf.ln(10)

    # Security Vulnerabilities
    pdf.set_font("Arial", "B", 14)
    pdf.cell(200, 10, "Security Vulnerabilities", ln=True)
    pdf.set_font("Arial", "", 12)
    for key, value in vulnerabilities["vulnerabilities"].items():
        pdf.cell(200, 8, f"{key.replace('_', ' ').title()}: {'Yes' if value else 'No'}", ln=True)

    pdf.cell(200, 10, f"Overall Risk Level: {vulnerabilities['risk_level']}", ln=True)
    pdf.ln(10)

    # Speed Test Results
    pdf.set_font("Arial", "B", 14)
    pdf.cell(200, 10, "Wi-Fi Speed Test Results", ln=True)
    pdf.set_font("Arial", "", 12)
    pdf.cell(200, 8, f"Download Speed: {speed_test_results['download']} Mbps", ln=True)
    pdf.cell(200, 8, f"Upload Speed: {speed_test_results['upload']} Mbps", ln=True)
    pdf.cell(200, 8, f"Ping: {speed_test_results['ping']} ms", ln=True)

    pdf.ln(10)

    # Recommendations
    pdf.set_font("Arial", "B", 14)
    pdf.cell(200, 10, "Security Recommendations", ln=True)
    pdf.set_font("Arial", "", 12)
    recommendations = [
        "Upgrade to WPA3 encryption if available.",
        "Use a strong, unique Wi-Fi password.",
        "Disable WPS to prevent brute-force attacks.",
        "Enable MAC address filtering for better security.",
        "Regularly check for unknown connected devices."
    ]
    for rec in recommendations:
        pdf.cell(200, 8, f"- {rec}", ln=True)

    # Save Report
    report_filename = f"audit_report_{datetime.datetime.now().strftime('%Y%m%d%H%M%S')}.pdf"
    report_path = os.path.join("reports", report_filename)
    os.makedirs("reports", exist_ok=True)
    pdf.output(report_path)

    return send_file(report_path, as_attachment=True)
    
# Run Flask App
if __name__ == '__main__':
    app.run(debug=True)
