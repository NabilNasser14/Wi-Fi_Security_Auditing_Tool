document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("checkVulnerabilities").addEventListener("click", checkVulnerabilities);
});

function fetchData(endpoint) {
    fetch(endpoint)
        .then(response => response.json())
        .then(data => {
            let outputBox = document.getElementById("output-box");
            outputBox.innerHTML = ""; 

            if (typeof data.output === 'object' && !Array.isArray(data.output)) {
                let formattedOutput = `<b>Interface name:</b> Wi-Fi<br>`;
                formattedOutput += `<b>Networks Visible:</b> ${Object.keys(data.output).length}<br><br>`;

                let count = 1;
                for (const [network, details] of Object.entries(data.output)) {
                    formattedOutput += `<b>SSID ${count}:</b> ${network}<br>`;

                    let password = details.password
                        ? `<span style="color: blue; font-weight: bold;">${details.password}</span>`
                        : "Not Available";
                    formattedOutput += `    <b>Password:</b> ${password}<br>`;

                    let strength = details.strength || "Unknown";
                    let strengthColor = strength;
                    if (strength.toLowerCase() === "weak") {
                        strengthColor = `<span style="color: red; font-weight: bold;">Weak</span>`;
                    } else if (strength.toLowerCase() === "moderate") {
                        strengthColor = `<span style="color: orange; font-weight: bold;">Moderate</span>`;
                    } else if (strength.toLowerCase() === "strong") {
                        strengthColor = `<span style="color: green; font-weight: bold;">Strong</span>`;
                    }

                    formattedOutput += `    <b>Strength:</b> ${strengthColor}<br><br>`;
                    count++;
                }
                outputBox.innerHTML = formattedOutput; 
            } else if (Array.isArray(data.output)) {
                outputBox.innerText = data.output.join("\n");
            } else {
                outputBox.innerText = data.output || data.error;
            }
        })
        .catch(error => console.error("Error:", error));
}

// Function mappings
function scanNetworks() { fetchData('/scan_networks'); }
function checkConnected() { fetchData('/check_connected'); }
function auditSecurity() { fetchData('/audit_security'); }
function checkPasswordSecurity() { fetchData('/check_password_security'); }

function checkVulnerabilities() {
    fetch('/check_vulnerabilities')
        .then(response => response.json())
        .then(data => {
            let reportHtml = `<h3>📡 Connected Network: <b>${data.connected_network.ssid}</b></h3>`;
            reportHtml += `
                <table>
                    <thead>
                        <tr>
                            <th>Vulnerability</th>
                            <th>Description</th>
                            <th>Risk Level</th>
                            <th>Your Network Status</th>
                        </tr>
                    </thead>
                    <tbody>`;

            // Check vulnerabilities dynamically
            const vulnerabilities = [
                { name: "Open Networks", desc: "No encryption, anyone can connect.", risk: "High", flag: data.connected_network.open_network },
                { name: "WEP Networks", desc: "Uses weak, outdated WEP encryption.", risk: "High", flag: data.connected_network.wep },
                { name: "Weak Passwords", desc: "Easily guessed, prone to brute-force.", risk: "High", flag: data.connected_network.weak_password },
                { name: "TKIP Encryption", desc: "Weak encryption, vulnerable to attacks.", risk: "High", flag: data.connected_network.tkip_encryption },
                { name: "Default SSID", desc: "Using a default SSID may expose default passwords.", risk: "Medium", flag: data.connected_network.default_ssid },
                { name: "WPS Enabled", desc: "WPS can be brute-forced, leading to easy access.", risk: "High", flag: data.connected_network.wps_enabled },
                { name: "Too Many Connected Devices", desc: "Potential unauthorized access.", risk: "Medium to High", flag: data.connected_network.too_many_devices }
            ];

            vulnerabilities.forEach(vuln => {
                let statusIcon = vuln.flag
                    ? `<span style="color: red;">❌ Vulnerable</span>`
                    : `<span style="color: green;">✅ Secure</span>`;
            
                    let riskColor = "black";
                    if (vuln.risk === "High") riskColor = "red";
                    else if (vuln.risk === "Medium") riskColor = "orange";
                    else if (vuln.risk === "Medium to High") riskColor = "darkorange";
                    else if (vuln.risk === "Low") riskColor = "green";
                reportHtml += `
                    <tr>
                        <td>${vuln.name}</td>
                        <td>${vuln.desc}</td>
                        <td>${getRiskLevelColor(vuln.risk)}</td>
                        <td>${statusIcon}</td>
                    </tr>`;
            });

            reportHtml += `</tbody></table>`;
            reportHtml += `<h3>Risk Level: <span style="color: ${data.risk_level === 'High' ? 'red' : data.risk_level === 'Medium' ? 'orange' : 'green'}">${data.risk_level}</span></h3>`;

            document.getElementById('output-box').innerHTML = reportHtml;
        })
        .catch(error => {
            console.error("Error fetching vulnerabilities:", error);
            document.getElementById('output-box').innerHTML = `<p style="color: red;">⚠️ Error retrieving vulnerability data.</p>`;
        });
}

function getRiskLevelColor(risk) {
    if (risk === "High") return '<span style="color: red; font-weight: bold;">High</span>';
    if (risk === "Medium") return '<span style="color: orange; font-weight: bold;">Medium</span>';
    if (risk === "Medium to High") return '<span style="color: darkorange; font-weight: bold;">Medium to High</span>';
    if (risk === "Low") return '<span style="color: green; font-weight: bold;">Low</span>';
    return `<span>${risk}</span>`; 
}

function generateAuditReport() {
    const reportButton = document.querySelector("li[onclick='generateAuditReport()']");
    const outputBox = document.querySelector(".output-box");

    // Disable button & show loading animation
    reportButton.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Generating...`;
    reportButton.style.pointerEvents = "none";

    outputBox.innerHTML = `
        <div class="loading-container">
            <i class="fas fa-spinner fa-spin"></i> Generating Audit Report...
        </div>
    `;

    fetch("/generate_audit_report")
        .then(response => {
            if (!response.ok) {
                throw new Error("Failed to generate the report.");
            }
            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);

            // Get the current date & time with switched month & day format (DD/MM/YYYY HH:MM:SS)
            const now = new Date();
            const formattedDate = now.toLocaleDateString("en-GB"); // "DD/MM/YYYY"
            const formattedTime = now.toLocaleTimeString(); // "HH:MM:SS"

            // Update Results Box with success message, timestamp, and download button
            outputBox.innerHTML = `
                <p class="success-message">
                    <i class="fas fa-check-circle"></i> Audit Report Generated Successfully!
                </p>
                <p class="report-timestamp">
                    <i class="fas fa-clock"></i> Generated on: ${formattedDate} ${formattedTime}
                </p>
                <a href="${url}" download="WiFi_Audit_Report.pdf" class="download-button">
                    <i class="fas fa-file-pdf"></i> Download Report
                </a>
            `;

            // Auto-download the report
            const a = document.createElement("a");
            a.href = url;
            a.download = "WiFi_Audit_Report.pdf";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        })
        .catch(error => {
            console.error("Error generating the report:", error);
            outputBox.innerHTML = `<p class="error-message">❌ Failed to generate audit report.</p>`;
        })
        .finally(() => {
            // Reset button after download or error
            reportButton.innerHTML = `<i class="fas fa-file-alt"></i> Generate Auditing Report`;
            reportButton.style.pointerEvents = "auto";
        });
}





