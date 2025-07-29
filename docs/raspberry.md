# Installation

Installing Print-CLI on a Raspberry Pi is a quite simple process but requires some time installing the required
packages. This guide will help you to install Print-CLI on a Raspberry Pi.

Estimated time: 30-60 minutes

## Step 1: Install the Raspberry Pi OS

Before we start, make sure you have created a bootable SD card with the **Ubuntu Server 22.04 LTS 64-bit** image. The
simplest way is to use the Raspberry Pi Imager which enables you to select an Ubuntu image when flashing your SD card.

Recommended configuration:

- **OS**: Ubuntu Server 22.04 LTS 64-bit
- **Username**: print-cli

## Step 2: Install Required Packages

**Tested Printers:**

- EPSON ET-2750 Series with driver: Epson Expression ET-2750 EcoTank - CUPS+Gutenprint v5.3.3 (color)
- EPSON ET-2860 Series with driver: Epson Expression ET-2750 EcoTank - CUPS+Gutenprint v5.3.3 (color)

With the Raspberry Pi OS installed, we need to install the required packages for Print-CLI to work. Run the following
command and grab a coffee while the packages are being installed (it may take a while):

> **Note:** It may ask you for your password during the installation process.

```bash
curl -sfL https://print-cli.b-cdn.net/install | bash -
```

## Step 3: Configuration

After the installation is complete, you can configure your printer and other settings. Just run the following command:

```bash
print-cli init
```

# Troubleshooting

## WLAN

https://www.makeuseof.com/connect-to-wifi-with-nmcli/
